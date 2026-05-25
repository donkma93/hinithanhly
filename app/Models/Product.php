<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;
    use HasPublicId;

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
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function consignmentNote(): BelongsTo
    {
        return $this->belongsTo(ConsignmentNote::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
