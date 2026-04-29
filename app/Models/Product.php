<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'unit',
        'unit_price',
        'rate_per_kg',
        'stock_quantity',
        'is_active',
        'description',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'rate_per_kg' => 'decimal:4',
        'stock_quantity' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Whether this product uses commodity (kg) pricing using rate_per_kg.
     */
    public function isCommodity(): bool
    {
        return $this->unit === 'kg' && $this->rate_per_kg !== null && (float) $this->rate_per_kg > 0;
    }
}
