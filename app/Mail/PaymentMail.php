<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
//use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

//class PaymentMail extends Mailable implements ShouldQueue
class PaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public $mailData)
    {
        //
    }

    public function build()
    {
        \Log::info("maildata: " . json_encode($this->mailData));

        return $this->subject($this->mailData['subject'])
            ->view($this->mailData['mailView'])
            ->with([
                'subject' => $this->mailData['subject'],
                'amount'  => $this->mailData['amount'],
                'refid'   => $this->mailData['refid'],
                'email'   => $this->mailData['email'],
            ]);
    }
}
