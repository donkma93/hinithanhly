<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use HasPublicId;
    use SoftDeletes;

    public const CONSIGNMENT_TERM_DAYS = 45;

    public const CONSIGNMENT_WARNING_DAYS = 7;

    protected $fillable = [
        'public_id',
        'consignment_note_id',
        'supplier_id',
        'category_id',
        'created_by_id',
        'name',
        'sale_price',
        'quantity',
        'image_path',
        'description',
        'returned_at',
        'returned_by_id',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'quantity' => 'integer',
        'returned_at' => 'datetime',
    ];

    public function consignmentNote(): BelongsTo
    {
        return $this->belongsTo(ConsignmentNote::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id')->withTrashed();
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_id')->withTrashed();
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    public function isSellable(): bool
    {
        return ! $this->isReturned() && ! $this->isConsignmentExpired();
    }

    public function consignmentSentDate(): ?\Illuminate\Support\Carbon
    {
        return $this->consignmentNote?->sent_date;
    }

    public function consignmentDueDate(): ?\Illuminate\Support\Carbon
    {
        $sentDate = $this->consignmentSentDate();

        return $sentDate?->copy()->startOfDay()->addDays(self::CONSIGNMENT_TERM_DAYS);
    }

    public function consignmentDaysRemaining(): ?int
    {
        $dueDate = $this->consignmentDueDate();

        if ($dueDate === null) {
            return null;
        }

        $today = now()->startOfDay();
        $dueDate = $dueDate->copy()->startOfDay();

        if ($today->equalTo($dueDate)) {
            return 0;
        }

        $days = $today->diffInDays($dueDate);

        return $today->greaterThan($dueDate) ? -$days : $days;
    }

    public function isConsignmentExpired(): bool
    {
        $dueDate = $this->consignmentDueDate();

        if ($dueDate === null) {
            return false;
        }

        return now()->startOfDay()->greaterThan($dueDate->copy()->startOfDay());
    }

    public function isConsignmentExpiringSoon(): bool
    {
        if ($this->isReturned()) {
            return false;
        }

        $daysRemaining = $this->consignmentDaysRemaining();

        return $daysRemaining !== null
            && $daysRemaining >= 0
            && $daysRemaining <= self::CONSIGNMENT_WARNING_DAYS;
    }

    public function getConsignmentStatusLabelAttribute(): string
    {
        if ($this->isReturned()) {
            return 'Đã trả cho người gửi';
        }

        $daysRemaining = $this->consignmentDaysRemaining();

        if ($daysRemaining === null) {
            return '---';
        }

        if ($this->isConsignmentExpired()) {
            return 'Quá hạn '.$this->formatConsignmentOffset(abs($daysRemaining)).' ngày';
        }

        if ($daysRemaining === 0) {
            return 'Hết hạn hôm nay';
        }

        return 'Còn '.$daysRemaining.' ngày';
    }

    public function getConsignmentStatusToneAttribute(): string
    {
        if ($this->isReturned()) {
            return 'gray';
        }

        if ($this->isConsignmentExpired()) {
            return 'danger';
        }

        if ($this->isConsignmentExpiringSoon()) {
            return 'warning';
        }

        return 'success';
    }

    public function scopeSellable(Builder $query): Builder
    {
        return $query
            ->whereNull('returned_at')
            ->whereHas('consignmentNote', function (Builder $consignmentQuery): void {
                $consignmentQuery->whereDate('sent_date', '>=', now()->subDays(self::CONSIGNMENT_TERM_DAYS)->toDateString());
            });
    }

    private function formatConsignmentOffset(int $days): int
    {
        return max($days, 0);
    }
}
