<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'supplier_id',
        'user_id',
        'payment_reference',
        'period_from',
        'period_to',
        'gross_amount',
        'discount_rate',
        'discount_amount',
        'payable_amount',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'qr_url',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'gross_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}