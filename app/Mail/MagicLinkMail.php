<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $link;

    public function __construct(string $link)
    {
        $this->link = $link;
    }

    public function build()
    {
        return $this->subject('DexyPay: Your Login Link')
            ->view('emails.magic-link')
            ->with(['link' => $this->link]);
    }
}
