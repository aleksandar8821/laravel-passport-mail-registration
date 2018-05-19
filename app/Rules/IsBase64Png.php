<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsBase64Png implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Ideju pokupio ovde https://stackoverflow.com/questions/39042731/validate-a-base64-decoded-image-in-laravel . Jos jedna metoda koja je fino prihvacena na stackoverflowu https://stackoverflow.com/questions/12658661/validating-base64-encoded-images?noredirect=1&lq=1 (nesto slicno imas i ovde https://hashnode.com/post/how-do-you-validate-a-base64-image-cjcftg4fy03s0p7wtn31oqjx7). E sad ni ovaj prvi odgovor nije kompletan nego ti treba ovaj preg_replace, a to imas pre svega ovde https://ourcodeworld.com/articles/read/76/how-to-save-a-base64-image-from-javascript-with-php (inace jedan od prvih rezultata na google) a i ovde istu stvar https://laracasts.com/discuss/channels/laravel/insert-base64-decoded-image-in-database , inace imas tu jos par nacina koji isto rade sa stringovima, ima nesto od toga na prvom linku, al ovo je izgleda najjednostavnije.

        //E da, inace kako se radi custom validacija na ovaj nacin sa ovim Rules, to sam pokupio ovde https://laracasts.com/series/whats-new-in-laravel-5-5/episodes/7 a imas to i u dokumentaciji 
        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $value));
        $f = finfo_open();
        $result = finfo_buffer($f, $image, FILEINFO_MIME_TYPE);
        return $result === 'image/png';

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The file must be an image!';
    }
}
