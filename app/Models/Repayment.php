<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Repayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'farmer_id',
        'operator_id',
        'amount',
        'applied_amount',
        'change_amount',
        'commodity_kg',
        'commodity_rate',
        'commodity_name',
        'method',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'commodity_kg' => 'decimal:3',
        'commodity_rate' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function debts(): BelongsToMany
    {
        return $this->belongsToMany(Debt::class, 'repayment_debt')
            ->withPivot(['amount_applied', 'debt_remaining_before', 'debt_remaining_after'])
            ->withTimestamps();
    }
}
