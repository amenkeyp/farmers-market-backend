<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TransactionService
{
    public function __construct(private CommodityService $commodity)
    {
    }

    /**
     * Create and complete a checkout transaction.
     *
     * @param array $data {
     *   farmer_id: int, type: 'cash'|'credit', items: [{product_id, quantity}],
     *   interest_rate?: float, paid_amount?: float, notes?: string, due_at?: string
     * }
     */
    public function checkout(array $data, User $operator): Transaction
    {
        return DB::transaction(function () use ($data, $operator) {
            // Lock the farmer row to prevent concurrent credit overruns.
            /** @var Farmer $farmer */
            $farmer = Farmer::query()
                ->whereKey($data['farmer_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $farmer->is_active) {
                throw new RuntimeException('Farmer is not active.');
            }

            $type = $data['type'];
            if (! in_array($type, [Transaction::TYPE_CASH, Transaction::TYPE_CREDIT], true)) {
                throw new RuntimeException('Invalid transaction type.');
            }

            // Lock products and build snapshot lines
            $productIds = collect($data['items'])->pluck('product_id')->unique()->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw new RuntimeException('One or more products not found.');
            }

            $subtotal = 0.0;
            $itemsPayload = [];

            foreach ($data['items'] as $line) {
                /** @var Product $product */
                $product = $products[$line['product_id']];

                if (! $product->is_active) {
                    throw new RuntimeException("Product {$product->sku} is not active.");
                }

                $qty = (float) $line['quantity'];
                if ($qty <= 0) {
                    throw new RuntimeException("Quantity must be > 0 for {$product->sku}.");
                }
                if ((float) $product->stock_quantity < $qty) {
                    throw new RuntimeException("Insufficient stock for {$product->sku} (have {$product->stock_quantity}, need {$qty}).");
                }

                $calc = $this->commodity->lineTotal($product, $qty);
                $subtotal += $calc['line_total'];

                $itemsPayload[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $calc['unit_price'],
                    'rate_per_kg' => $calc['rate_per_kg'],
                    'line_total' => $calc['line_total'],
                ];
            }

            $subtotal = round($subtotal, 2);

            // Interest only applies to credit
            $interestRate = 0.0;
            if ($type === Transaction::TYPE_CREDIT) {
                $interestRate = isset($data['interest_rate'])
                    ? (float) $data['interest_rate']
                    : (float) config('market.default_interest_rate', 0);
                if ($interestRate < 0) {
                    throw new RuntimeException('Interest rate cannot be negative.');
                }
            }

            $interestAmount = round($subtotal * $interestRate, 2);
            $totalAmount = round($subtotal + $interestAmount, 2);

            $paidAmount = 0.0;
            if ($type === Transaction::TYPE_CASH) {
                $paidAmount = (float) ($data['paid_amount'] ?? $totalAmount);
                if ($paidAmount + 0.005 < $totalAmount) {
                    throw new RuntimeException("Cash paid_amount ({$paidAmount}) is less than total ({$totalAmount}).");
                }
                // Cash must equal total exactly (overpayment refunded externally; we don't track change here)
                $paidAmount = $totalAmount;
            }

            // CREDIT LIMIT ENFORCEMENT
            if ($type === Transaction::TYPE_CREDIT) {
                $newDebt = (float) $farmer->current_debt + $totalAmount;
                if ($newDebt > (float) $farmer->credit_limit + 0.005) {
                    throw new RuntimeException(sprintf(
                        'Credit limit exceeded: current_debt=%s + new_credit=%s > credit_limit=%s',
                        number_format((float) $farmer->current_debt, 2, '.', ''),
                        number_format($totalAmount, 2, '.', ''),
                        number_format((float) $farmer->credit_limit, 2, '.', ''),
                    ));
                }
            }

            $now = Carbon::now();
            $reference = $this->generateReference('TXN');

            /** @var Transaction $transaction */
            $transaction = Transaction::create([
                'reference' => $reference,
                'farmer_id' => $farmer->id,
                'operator_id' => $operator->id,
                'type' => $type,
                'status' => Transaction::STATUS_COMPLETED,
                'subtotal' => $subtotal,
                'interest_rate' => $interestRate,
                'interest_amount' => $interestAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'notes' => $data['notes'] ?? null,
                'completed_at' => $now,
            ]);

            foreach ($itemsPayload as $line) {
                /** @var Product $product */
                $product = $line['product'];
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'rate_per_kg' => $line['rate_per_kg'],
                    'line_total' => $line['line_total'],
                ]);

                // Decrement stock atomically
                $product->decrement('stock_quantity', $line['quantity']);
            }

            // Credit -> create debt + update farmer running balance
            if ($type === Transaction::TYPE_CREDIT) {
                Debt::create([
                    'farmer_id' => $farmer->id,
                    'transaction_id' => $transaction->id,
                    'original_amount' => $totalAmount,
                    'remaining_amount' => $totalAmount,
                    'status' => Debt::STATUS_OPEN,
                    'issued_at' => $now,
                    'due_at' => isset($data['due_at']) ? Carbon::parse($data['due_at']) : null,
                ]);

                $farmer->increment('current_debt', $totalAmount);
            }

            return $transaction->fresh(['items', 'debt', 'farmer', 'operator']);
        });
    }

    public function generateReference(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, now()->format('Ymd'), Str::upper(Str::random(6)));
    }
}
