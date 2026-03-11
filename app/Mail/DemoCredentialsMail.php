<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $ownerName,
        public readonly string $ownerEmail,
        public readonly string $password,
        public readonly string $companyName,
        public readonly ?string $employeeEmail = null,
        public readonly string $employeePassword = 'demo1234',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Demo PayrollKami Anda Sudah Siap! 🚀',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-credentials',
        );
    }
}
