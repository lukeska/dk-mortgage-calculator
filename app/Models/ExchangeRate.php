<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    /** @use HasFactory<\Database\Factories\ExchangeRateFactory> */
    use HasFactory;

    protected $fillable = [
        'currency_code',
        'currency_name',
        'rate',
        'fetched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * Convert an amount from DKK to this currency.
     */
    public function convertFromDkk(float $amountInDkk): float
    {
        return $amountInDkk * (float) $this->rate;
    }

    /**
     * Convert an amount from this currency to DKK.
     */
    public function convertToDkk(float $amountInCurrency): float
    {
        return $amountInCurrency / (float) $this->rate;
    }
}
