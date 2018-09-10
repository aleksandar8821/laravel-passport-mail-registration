<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SafePasswordResetStyled extends Mailable
{
    use Queueable, SerializesModels;

    public $email; 
    public $user_first_name; 
    public $password_reset_token; 
    public $allow_access_token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $user_first_name, $password_reset_token, $allow_access_token)
    {
        $this->email = $email;
        $this->user_first_name = $user_first_name;
        $this->password_reset_token = $password_reset_token;
        $this->allow_access_token = $allow_access_token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.safe-password-reset-styled');
    }
}
