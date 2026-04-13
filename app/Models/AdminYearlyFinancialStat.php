<?php

namespace App\Models;

use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdminYearlyFinancialStat extends Model
{
    protected $table = 'admin_yearly_financial_stats';

    protected $fillable = [
        'year',
        'sold_purchase_count',
        'distinct_sold_tests_count',
        'gross_sales_amount',
        'users_profit_amount',
        'platform_net_profit_amount',
        'average_monthly_sales_amount',
        'average_monthly_platform_profit_amount',
        'most_purchased_test_id',
        'most_purchased_test_purchase_count',
    ];

    protected $casts = [
        'year' => 'integer',
        'sold_purchase_count' => 'integer',
        'distinct_sold_tests_count' => 'integer',
        'gross_sales_amount' => 'decimal:2',
        'users_profit_amount' => 'decimal:2',
        'platform_net_profit_amount' => 'decimal:2',
        'average_monthly_sales_amount' => 'decimal:2',
        'average_monthly_platform_profit_amount' => 'decimal:2',
        'most_purchased_test_purchase_count' => 'integer',
    ];

    public function mostPurchasedTest(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'most_purchased_test_id');
    }
}
