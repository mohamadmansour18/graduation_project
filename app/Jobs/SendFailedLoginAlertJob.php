<?php

namespace App\Jobs;

use App\Mail\FailedLoginAlertMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFailedLoginAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public User $user,
        public int $attemptsCount,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    )
    {
        $this->onQueue('medium');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \Mail::to($this->user->email)
            ->send(new FailedLoginAlertMail(
                user: $this->user,
                attemptsCount: $this->attemptsCount,
                ipAddress: $this->ipAddress,
                userAgent: $this->userAgent,
            ));
    }

    public function failed(\Throwable $exception):void
    {
        Log::channel('errors')->error('Security alert email job failed', [
            'user_id' => $this->user->id,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'exception_message' => $exception->getMessage(),
            'exception_class' => $exception::class,
        ]);
    }
}
