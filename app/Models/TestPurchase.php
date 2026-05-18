<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestPurchase extends Model
{

    protected $table = 'test_purchases';

    protected $fillable = [
        'test_id',
        'buyer_user_id',
        'seller_user_id',
        'gross_amount',
        'platform_fee_amount',
        'seller_net_amount',
        'currency',
        'payment_provider',
        'payment_reference',
        'payment_status',
        'purchased_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'seller_net_amount' => 'decimal:2',
        'purchased_at' => 'datetime',
        'payment_status' => PaymentStatus::class,
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function buyerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function sellerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class, 'test_purchase_id');
    }
}

