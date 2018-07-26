<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/*
Ovaj email se salje kada je korisnik koji je poslao request za promenu podataka naveo i novi mail. Konfirmaciju trazim samo na novom mailu, a na starom ne trazim konfirmaciju nego samo obavestavam da mu se podaci na nalogu menjaju .................*/

class UserUpdateOldEmailStyled extends Mailable
{
    use Queueable, SerializesModels;

    public $user_first_name; //automatski dostupni view-u ako su public
    public $user_id;
    public $user_update_id;
    public $token; // Pomocu ovog tokena user moze sa starog maila ili da blokira request ukoliko jos nije odobren sa novog maila, ili ukoliko je odobren da ponisti promene koje su tom prilikom nastale

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user_first_name, $user_id, $user_update_id, $block_request_or_revoke_changes_token)
    {
        $this->user_first_name = $user_first_name;
        $this->user_id = $user_id;
        $this->user_update_id = $user_update_id;
        $this->token = $block_request_or_revoke_changes_token; // Pomocu ovog tokena user moze sa starog maila ili da blokira request ukoliko jos nije odobren sa novog maila, ili ukoliko je odobren da ponisti promene koje su tom prilikom nastale
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.user-update-old-email-styled');
    }
}
