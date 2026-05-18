<?php

namespace App\Models;

use App\Enums\Payments\PaymentAttemptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'test_purchase_id',
        'payment_provider',
        'provider_reference',
        'provider_payment_intent_reference',
        'checkout_url',
        'amount',
        'currency',
        'status',
        'failure_code',
        'failure_message',
        'expires_at',
        'paid_at',
        'failed_at',
        'expired_at',
        'cancelled_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',

        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',

        'status' => PaymentAttemptStatus::class,
    ];

    public function testPurchase(): BelongsTo
    {
        return $this->belongsTo(TestPurchase::class, 'test_purchase_id');
    }
}
