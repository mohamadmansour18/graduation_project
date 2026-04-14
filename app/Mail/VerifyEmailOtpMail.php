<?php

namespace App\Mail;

use App\Enums\PurposeOTP;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otpCode,
        public string $purpose
    )
    {}

    public function envelope(): Envelope
    {
        if($this->purpose === PurposeOTP::Email_Verification->value) {
            return new Envelope(
                subject: 'رمز التحقق الخاص بك لتأكيد بريدك الالكتروني',
            );
        } else {
            return new Envelope(
                subject: 'رمز التحقق الخاص بك لاعادة تعين كلمة المرور'
            );
        }
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verify-email-otp',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
