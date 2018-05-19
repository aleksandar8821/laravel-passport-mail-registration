<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\My_custom_files\unixTimestampFromDate;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // Ovo je moj custom trait u kojem se nalaze funkcije koje onaj timestamp koji po defaultu laravel stavlja u bazu konvertuje u UNIX timestamp i mnozi ga sa 1000 da bi angularov date pipe mogao da ga koristi (nasao foru na linkovima https://stackoverflow.com/questions/25534721/angularjs-unixtime-to-datetime-format-with-date-filter-failed , https://stackoverflow.com/questions/17925020/angularjs-convert-tag-value-unix-time-to-human-readable-time), jer on radi sa milisekundama (vidi expression na https://angular.io/api/common/DatePipe), a ovaj timestamp koji mi daje laravel je u sekundama. Sad jbg, imaces tri nule na kraju kad pomnozis sa 1000, tako da nemas tacan podatak o milisekundama, ali one ti uglavnom nece trebati, koji ce ti milisekunde kad prikazujes datume u browseru... Inace pravim trait da ne bi morao na svakom modelu da definisem ove funkcije nego samo uvozim trait (preporuceno ovde u postu od Koby https://stackoverflow.com/questions/31635427/laravel-5-global-date-accessor), a kako napraviti custom trait u laravelu imas ovde https://stackoverflow.com/questions/40288657/how-to-make-a-trait-in-laravel

    use unixTimestampFromDate;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'verified', 'verify_token', 'profile_image'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    public function galleries()
    {
        return $this->hasMany(Gallery::class);
    }

    public function gallery_comments()
    {
        return $this->hasMany(GalleryComment::class);
    }

    public function image_comments()
    {
        return $this->hasMany(ImageComment::class);
    }

}
