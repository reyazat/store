<?php

namespace IamApi\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param string $code    کد OTP شش‌رقمی
     * @param string $purpose هدف (login_verification, password_reset, ...)
     * @param int    $expiryMinutes مدت اعتبار به دقیقه
     */
    public function __construct(
        public readonly string $code,
        public readonly string $purpose,
        public readonly int    $expiryMinutes = 10,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            'login_verification' => 'کد ورود به حساب کاربری',
            'password_reset'     => 'کد بازنشانی رمز عبور',
            'email_verification' => 'تأیید آدرس ایمیل',
            'phone_verification' => 'تأیید شماره موبایل',
            'account_activation' => 'فعال‌سازی حساب کاربری',
            'security_verification' => 'تأیید هویت امنیتی',
        ];

        $subject = $subjects[$this->purpose] ?? 'کد تأیید';

        return new Envelope(
            subject: config('app.name', 'سامانه') . ' - ' . $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'iam::mail.otp',
            with: [
                'code'          => $this->code,
                'purpose'       => $this->purpose,
                'expiryMinutes' => $this->expiryMinutes,
                'appName'       => config('app.name', 'سامانه'),
                'purposeLabel'  => $this->getPurposeLabel(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get human-readable label for OTP purpose
     */
    private function getPurposeLabel(): string
    {
        $labels = [
            'login_verification'    => 'ورود به حساب کاربری',
            'password_reset'        => 'بازنشانی رمز عبور',
            'email_verification'    => 'تأیید آدرس ایمیل',
            'phone_verification'    => 'تأیید شماره موبایل',
            'account_activation'    => 'فعال‌سازی حساب کاربری',
            'security_verification' => 'تأیید هویت امنیتی',
        ];

        return $labels[$this->purpose] ?? 'تأیید هویت';
    }
}
