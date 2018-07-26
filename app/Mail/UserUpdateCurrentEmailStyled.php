<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/*
Ovaj email saljem kada korisnik nije naveo novi mail u formi za promenu podaataka. Dakle saljem mu mail na trenutnu adresu, trazim mu naravno konfirmaciju preko linka. A ukoliko se desi da vlasnik emaila nije taj koji je slao request za promenu podataka, to znaci da je neko upao na njegov nalog i poslao request. U tom slucaju naravno vlasnik maila nece kliknuti na link za konfirmaciju, ali bi mu se mogla dati opcija da blokira nalog ili je resenje da se on sam uloguje i da promeni sifru da mu ovaj hacker vise ne upada na nalog ili tako nesto.
*/

class UserUpdateCurrentEmailStyled extends Mailable
{
    use Queueable, SerializesModels;

    public $email; //automatski dostupni view-u ako su public
    public $user_id;
    public $user_first_name;
    public $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $user_id, $user_first_name, $token)
    {
        $this->email = $email;
        $this->user_id = $user_id;
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
        return $this->markdown('emails.user-update-current-email-styled');
    }
}
