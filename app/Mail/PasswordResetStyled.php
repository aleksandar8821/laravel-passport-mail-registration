<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetStyled extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $user_first_name;
    public $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $user_first_name, $token)
    {
        $this->email = $email;
        $this->user_first_name = $user_first_name;
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('password_reset@afgalleries.com')->markdown('emails.password-reset-styled');
    }
}
