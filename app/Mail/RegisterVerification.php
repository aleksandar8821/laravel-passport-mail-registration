<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterVerification extends Mailable
{
    use Queueable, SerializesModels;

    public $createdUser; //automatski dostupan view-u ako je public

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $createdUser)
    {
        $this->createdUser = $createdUser;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('verification@afgalleries.com')->view('emails.register-verification');
    }
}
