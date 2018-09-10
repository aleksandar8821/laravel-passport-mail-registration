<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SafeAccessStyled extends Mailable
{
    use Queueable, SerializesModels;

    public $user_first_name;
    public $allow_access_token;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user_first_name, $allow_access_token)
    {
        $this->user_first_name = $user_first_name;
        $this->allow_access_token = $allow_access_token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.safe-access-styled');
    }
}
