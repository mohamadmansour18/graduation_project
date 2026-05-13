<?php

namespace App\Models;

use App\Enums\TestReportsReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestReportReasonCounter extends Model
{
    protected $table = 'test_report_reason_counters';

    protected $fillable = [
        'test_id',
        'reason',
        'approval_version',
        'reporters_count'
    ];

    protected $casts = [
        'reason' => TestReportsReason::class
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }
}
