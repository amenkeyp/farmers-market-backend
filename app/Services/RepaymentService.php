<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Repayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RepaymentService
{
    /**
     * Apply a repayment to a farmer's debts using FIFO (oldest issued_at first).
     * Supports partial repayment, exact payment, and overpayment.
     *
     * Edge cases handled:
     *  - Zero outstanding debt   -> reject
     *  - Amount <= 0             -> reject
     *  - Overpayment             -> applied_amount = total_outstanding, change_amount = amount - applied
     *  - Exact payment           -> applied = amount, change = 0
     *  - Partial repayment       -> oldest debt(s) reduced, remainder stops at first non-cleared debt
     *
     * Commodity repayment:
     *  - When commodity_kg + commodity_rate are provided, amount = kg * rate (auto-computed)
     *  - The kg, rate, and commodity_name are stored for audit
     *
     * @param array $data { farmer_id, amount?, commodity_kg?, commodity_rate?, commodity_name?, method?, paid_at?, notes? }
     */
    public function repay(array $data, User $operator): Repayment
    {
        return DB::transaction(function () use ($data, $operator) {
            // Commodity conversion: kg × rate → FCFA
            $commodityKg = isset($data['commodity_kg']) ? round((float) $data['commodity_kg'], 3) : null;
            $commodityRate = isset($data['commodity_rate']) ? round((float) $data['commodity_rate'], 2) : null;
            $commodityName = $data['commodity_name'] ?? null;

            if ($commodityKg !== null && $commodityRate !== null && $commodityKg > 0 && $commodityRate > 0) {
                $amount = round($commodityKg * $commodityRate, 2);
            } else {
                $amount = round((float) ($data['amount'] ?? 0), 2);
            }

            if ($amount <= 0) {
                throw new RuntimeException('Repayment amount must be greater than zero.');
            }

            /** @var Farmer $farmer */
            $farmer = Farmer::query()
                ->whereKey($data['farmer_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Lock open debts FIFO (oldest first)
            $debts = Debt::query()
                ->where('farmer_id', $farmer->id)
                ->whereIn('status', [Debt::STATUS_OPEN, Debt::STATUS_PARTIALLY_PAID])
                ->orderBy('issued_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $totalOutstanding = round((float) $debts->sum('remaining_amount'), 2);
            if ($totalOutstanding <= 0) {
                throw new RuntimeException('Farmer has no outstanding debts.');
            }

            $remainingToApply = min($amount, $totalOutstanding);
            $appliedTotal = 0.0;
            $allocations = [];

            foreach ($debts as $debt) {
                if ($remainingToApply <= 0) {
                    break;
                }

                $debtRemaining = (float) $debt->remaining_amount;
                if ($debtRemaining <= 0) {
                    continue;
                }

                $apply = round(min($debtRemaining, $remainingToApply), 2);
                $newRemaining = round($debtRemaining - $apply, 2);

                $debt->remaining_amount = $newRemaining;
                if ($newRemaining <= 0.0001) {
                    $debt->remaining_amount = 0;
                    $debt->status = Debt::STATUS_PAID;
                    $debt->paid_at = Carbon::now();
                } else {
                    $debt->status = Debt::STATUS_PARTIALLY_PAID;
                }
                $debt->save();

                $allocations[] = [
                    'debt_id' => $debt->id,
                    'amount_applied' => $apply,
                    'debt_remaining_before' => $debtRemaining,
                    'debt_remaining_after' => (float) $debt->remaining_amount,
                ];

                $appliedTotal = round($appliedTotal + $apply, 2);
                $remainingToApply = round($remainingToApply - $apply, 2);
            }

            $changeAmount = round($amount - $appliedTotal, 2);

            /** @var Repayment $repayment */
            $repayment = Repayment::create([
                'reference' => sprintf('RPY-%s-%s', now()->format('Ymd'), Str::upper(Str::random(6))),
                'farmer_id' => $farmer->id,
                'operator_id' => $operator->id,
                'amount' => $amount,
                'applied_amount' => $appliedTotal,
                'change_amount' => $changeAmount,
                'commodity_kg' => $commodityKg,
                'commodity_rate' => $commodityRate,
                'commodity_name' => $commodityName,
                'method' => $commodityKg !== null ? 'commodity' : ($data['method'] ?? 'cash'),
                'notes' => $data['notes'] ?? null,
                'paid_at' => isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : Carbon::now(),
            ]);

            $pivotRows = [];
            foreach ($allocations as $a) {
                $pivotRows[$a['debt_id']] = [
                    'amount_applied' => $a['amount_applied'],
                    'debt_remaining_before' => $a['debt_remaining_before'],
                    'debt_remaining_after' => $a['debt_remaining_after'],
                ];
            }
            $repayment->debts()->attach($pivotRows);

            // Decrement farmer running balance by applied amount
            $farmer->decrement('current_debt', $appliedTotal);
            // Guard against drift
            if ((float) $farmer->fresh()->current_debt < 0) {
                $farmer->update(['current_debt' => 0]);
            }

            return $repayment->load(['debts', 'farmer', 'operator']);
        });
    }
}
