<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdminYearlyFinancialMonthStat extends Model
{
    protected $table = 'admin_yearly_financial_month_stats';

    protected $fillable = [
        'year',
        'month_no',
        'sold_purchase_count',
        'distinct_sold_tests_count',
        'gross_sales_amount',
        'users_profit_amount',
        'platform_net_profit_amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'month_no' => 'integer',
        'sold_purchase_count' => 'integer',
        'distinct_sold_tests_count' => 'integer',
        'gross_sales_amount' => 'decimal:2',
        'users_profit_amount' => 'decimal:2',
        'platform_net_profit_amount' => 'decimal:2',
    ];
}
