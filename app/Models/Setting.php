<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const SUPPLIER_DISCOUNT_KEYS = [
        'cho_tang',
        'khach_si',
        'ncc_it_san_pham',
        'ncc_nhieu_san_pham',
        'hang_thu_mua',
    ];

    public static function get(string $key, $default = null)
    {
        $record = static::where('key', $key)->first();

        return $record ? $record->value : $default;
    }

    public static function set(string $key, $value): Model
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function supplierDiscountRates(): array
    {
        $defaults = [
            'cho_tang' => 0,
            'khach_si' => 0,
            'ncc_it_san_pham' => 0,
            'ncc_nhieu_san_pham' => 0,
            'hang_thu_mua' => 0,
        ];

        foreach (self::SUPPLIER_DISCOUNT_KEYS as $key) {
            $defaults[$key] = (float) static::get("supplier_discount_{$key}", $defaults[$key]);
        }

        return $defaults;
    }

    public static function supplierDiscountRate(string $supplierType, float $default = 0): float
    {
        return (float) static::get("supplier_discount_{$supplierType}", $default);
    }

    public static function resolveBankCode(string $storedValue): string
    {
        $storedValue = trim($storedValue);
        if ($storedValue === '') {
            return '';
        }

        $bankOptions = config('banks', []);

        if (array_key_exists($storedValue, $bankOptions)) {
            return $storedValue;
        }

        $resolved = array_search($storedValue, $bankOptions, true);

        return $resolved !== false ? (string) $resolved : $storedValue;
    }

    public static function resolveBankLabel(string $storedValue): string
    {
        $bankOptions = config('banks', []);
        $storedValue = trim($storedValue);

        if ($storedValue === '') {
            return '';
        }

        if (array_key_exists($storedValue, $bankOptions)) {
            return $bankOptions[$storedValue];
        }

        return $storedValue;
    }
}
