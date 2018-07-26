<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/*
Ovaj mail saljes kada korisnik promeni mail i saljes ga na taj promenjeni mail. Trazis potvrdu preko linka, a ukoliko vlasnik maila nije napravio request za promenu podataka, to samo moze da znaci da je korisnik koji je ispunjavao formu za promenu podataka, naveo tudji mail u polju za mail. Nije mogao navesti mail nekog korisnika koji ti vec stoji u bazi, jer sam naveo u validaciji da se ne moze navesti vec postojeci mail iz baze. Dakle mogao je navesti samo neki mail koji se ne nalazi u tvojoj bazi podataka. Pa ukoliko je mail stigao na tu neku pogresnu adresu, ti se samo izvinjavas vlasniku adrese i kazes mu da ignorise mail.
*/

class UserUpdateNewEmailStyled extends Mailable
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
        return $this->markdown('emails.user-update-new-email-styled');
    }
}
