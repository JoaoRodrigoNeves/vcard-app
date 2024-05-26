<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailService extends Mailable
{
    use Queueable, SerializesModels;
    public $emailToken;
    public $name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($emailToken, $name)
    {
        $this->emailToken = $emailToken;
        $this->name = $name;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('vcard@mail.com', 'VCard Support')
            ->subject('Conta Administrador - Definição de password')
            ->markdown('mail.emailexample')
            ->with([
                'emailToken' => $this->emailToken,
                'name' => $this->name
            ]);
    }
}
