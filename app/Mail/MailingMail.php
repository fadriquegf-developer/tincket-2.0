<?php

namespace App\Mail;

use App\Models\Mailing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MailingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Mailing $mailing;
    public array $bccAddresses;
    public bool $isTest;

    public function __construct(Mailing $mailing, bool $isTest = false, array $bcc = [])
    {
        $this->mailing = Mailing::findOrFail($mailing->id);
        $this->isTest = $isTest;
        $this->bccAddresses = $bcc;

    }

    public function build(): self
    {
        (new \App\Http\Middleware\CheckBrandHost())
            ->loadBrandConfig($this->mailing->brand->code_name);

        $from = brand_setting('mail.from', ['address' => null, 'name' => null]);
        if ($from['address']) {
            $this->from($from['address'], $from['name']);
        }

        if (!empty($this->bccAddresses)) {
            $this->bcc($this->bccAddresses);
        }

        return $this->subject($this->mailing->subject)
            ->view(brand_setting('base.emails.basic-mailing-layout'), ['mailing' => $this->mailing])
            ->text(brand_setting('base.emails.basic-mailing-text'), ['mailing' => $this->mailing]);
    }
}
