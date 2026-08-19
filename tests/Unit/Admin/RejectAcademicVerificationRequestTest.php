<?php

namespace Tests\Unit\Admin;

use App\Http\Requests\Admin\RejectAcademicVerificationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RejectAcademicVerificationRequestTest extends TestCase
{
    public function test_rejection_reason_accepts_up_to_one_hundred_words(): void
    {
        $request = new RejectAcademicVerificationRequest;
        $reason = implode(' ', array_fill(0, 100, 'كلمة'));

        $validator = Validator::make(
            ['rejection_reason' => $reason],
            $request->rules(),
            $request->messages(),
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rejection_reason_rejects_more_than_one_hundred_words(): void
    {
        $request = new RejectAcademicVerificationRequest;
        $reason = implode(' ', array_fill(0, 101, 'كلمة'));

        $validator = Validator::make(
            ['rejection_reason' => $reason],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'سبب الرفض يجب ألا يتجاوز 100 كلمة',
            $validator->errors()->first('rejection_reason'),
        );
    }
}
