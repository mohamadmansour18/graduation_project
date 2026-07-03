<?php

namespace App\Jobs;

use App\Mail\SupervisorAccountCreatedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSupervisorAccountCreatedMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 20;

    public function __construct(
        public User $supervisor,
        public User $owner,
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->supervisor->email)
            ->send(new SupervisorAccountCreatedMail(
                supervisor: $this->supervisor,
                owner: $this->owner,
            ));

        Log::info('Supervisor account creation email sent successfully', [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_email' => $this->supervisor->email,
            'owner_id' => $this->owner->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('errors')->error('Supervisor account creation email job failed', [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_email' => $this->supervisor->email,
            'owner_id' => $this->owner->id,
            'exception_message' => $exception->getMessage(),
            'exception_class' => $exception::class,
        ]);
    }
}
