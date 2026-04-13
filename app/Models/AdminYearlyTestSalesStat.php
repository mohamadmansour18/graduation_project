<?php

namespace App\Models;

use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdminYearlyTestSalesStat extends Model
{

    protected $table = 'admin_yearly_test_sales_stats';

    protected $fillable = [
        'year',
        'test_id',
        'purchase_count',
        'gross_sales_amount',
        'users_profit_amount',
        'platform_net_profit_amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'purchase_count' => 'integer',
        'gross_sales_amount' => 'decimal:2',
        'users_profit_amount' => 'decimal:2',
        'platform_net_profit_amount' => 'decimal:2',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }
}
