<?php

namespace App\Mail;

use App\Models\Mailing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailingMail extends Mailable 
{
    use Queueable, SerializesModels;

    public Mailing $mailing;
    public array $bccAddresses;
    public bool $isTest;

    /**
     * Create a new message instance.
     */

    public function __construct(Mailing $mailing, bool $isTest = false, array $bcc = [])
    {
        $this->mailing = $mailing;
        $this->isTest = $isTest;
        $this->bccAddresses = $bcc;
    }

    public function build(): self
    {
        // El from ahora se configura desde el MailerService
        // Solo lo configuramos aquí si no viene ya configurado
        $from = $this->mailing->brand->mail_from ?? brand_setting('mail.from', ['address' => null, 'name' => null]);

        if ($from && isset($from['address']) && $from['address']) {
            $this->from($from['address'], $from['name'] ?? null);
        }

        // Configurar BCCs
        if (!empty($this->bccAddresses)) {
            $this->bcc($this->bccAddresses);
        }

        // Si es un test, añadir indicador al subject
        $subject = $this->isTest
            ? '[TEST] ' . $this->mailing->subject
            : $this->mailing->subject;

        // Usar las vistas de la brand
        $viewPath = brand_setting('base.emails.basic-mailing-layout', 'emails.mailing');
        $textPath = brand_setting('base.emails.basic-mailing-text', 'emails.mailing-text');

        return $this->subject($subject)
            ->view($viewPath, [
                'mailing' => $this->mailing,
                'isTest' => $this->isTest,
                'brandName' => $this->mailing->brand->name,
                'unsubscribeUrl' => $this->generateUnsubscribeUrl()
            ])
            ->text($textPath, [
                'mailing' => $this->mailing,
                'isTest' => $this->isTest
            ]);
    }

    /**
     * Generar URL de desuscripción
     */
    protected function generateUnsubscribeUrl(): string
    {
        // Generar URL segura para desuscripción
        // Podrías usar un token único o encriptar el email
        $baseUrl = config('app.url');
        $brandSlug = $this->mailing->brand->code_name;

        return "{$baseUrl}/{$brandSlug}/unsubscribe?mailing={$this->mailing->id}";
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments(): array
    {
        // Si necesitas adjuntos, puedes agregarlos aquí
        return [];
    }

    /**
     * Get the message envelope.
     * Esto es nuevo en Laravel 9+
     */
    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        $from = $this->mailing->brand->mail_from ?? brand_setting('mail.from');

        return new \Illuminate\Mail\Mailables\Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                $from['address'] ?? config('mail.from.address'),
                $from['name'] ?? config('mail.from.name')
            ),
            subject: $this->isTest
                ? '[TEST] ' . $this->mailing->subject
                : $this->mailing->subject,
        );
    }

    /**
     * Get the message content definition.
     * Alternativa moderna para Laravel 10+
     */
    public function content(): \Illuminate\Mail\Mailables\Content
    {
        $viewPath = brand_setting('base.emails.basic-mailing-layout', 'emails.mailing');
        $textPath = brand_setting('base.emails.basic-mailing-text', 'emails.mailing-text');

        return new \Illuminate\Mail\Mailables\Content(
            view: $viewPath,
            text: $textPath,
            with: [
                'mailing' => $this->mailing,
                'isTest' => $this->isTest,
                'brandName' => $this->mailing->brand->name,
                'brandLogo' => $this->mailing->brand->logo_url ?? null,
                'unsubscribeUrl' => $this->generateUnsubscribeUrl(),
                'viewInBrowserUrl' => $this->generateViewInBrowserUrl(),
            ],
        );
    }

    /**
     * Generar URL para ver en el navegador
     */
    protected function generateViewInBrowserUrl(): string
    {
        $baseUrl = config('app.url');
        $brandSlug = $this->mailing->brand->code_name;

        // Podrías usar un token temporal o signed URL
        return "{$baseUrl}/{$brandSlug}/mailing/{$this->mailing->slug}";
    }

    /**
     * Headers personalizados para tracking, anti-spam, etc.
     */
    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        return new \Illuminate\Mail\Mailables\Headers(
            messageId: null,
            references: [],
            text: [
                'X-Mailing-Id' => $this->mailing->id,
                'X-Brand-Id' => $this->mailing->brand_id,
                'X-Campaign-Name' => $this->mailing->name,
                'List-Unsubscribe' => '<' . $this->generateUnsubscribeUrl() . '>',
                'Precedence' => 'bulk',
            ],
        );
    }
}
