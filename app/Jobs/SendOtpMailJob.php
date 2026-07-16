<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public User $user,
        public string $otpCode,
        public string $purpose
    )
    {
        $this->onQueue('medium');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(new \App\Mail\VerifyEmailOtpMail($this->user , $this->otpCode , $this->purpose));

        Log::info('OTP email sent successfully', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'purpose' => $this->purpose,
        ]);
    }

    public function failed(\Throwable $exception):void
    {
        Log::channel('errors')->error('OTP email job failed', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'purpose' => $this->purpose,
            'exception_message' => $exception->getMessage(),
            'exception_class' => $exception::class,
        ]);
    }
}
