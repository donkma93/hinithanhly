<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Sale extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'user_id',
        'payment_method',
        'payment_reference',
        'total_amount',
        'items_count',
        'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'items_count' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function setCompletedAtAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['completed_at'] = null;

            return;
        }

        $this->attributes['completed_at'] = Carbon::parse($value)
            ->timezone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'))
            ->format($this->getDateFormat());
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
