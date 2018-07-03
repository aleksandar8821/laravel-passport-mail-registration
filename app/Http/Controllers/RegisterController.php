<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Mail\RegisterVerification;
use App\Rules\IsBase64Png;

class RegisterController extends Controller
{
    public function register(Request $request){

        // Laravel prilikom ajax zahteva automatski vraca greske u json formatu, a prilikom regularnog http zahteva pravi redirekciju msm na home stranicu i tamo bi trebalo da su ti dostupni errori u json formatu!

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'min:8', 'regex:~[0-9]~', 'confirmed'],
            'password_confirmation' => ['required', 'min:8', 'regex:~[0-9]~'],
            'accepted_terms' => 'accepted'
        ]);



        $createdUser = User::create([
        	'first_name' => $request->first_name,
        	'last_name' => $request->last_name,
        	'email' => $request->email,
        	'password' => bcrypt($request->password),
            'verify_token' => str_random(25).strval(time()) //dodajem ovde time zbog mogucnosti da se dvojici ili vise usera dostavi isti token ako koristim samo str_random. Kad bi imali isti token, postojala bi mogucnost, da kasnije pri verifikaciji ti trazis usera u bazi po tokenu koji ti je poslao i da ti upit vrati vise usera! I stas onda?! Mada realno, ti mozes slati i id usera preko mail-a (ne znam kolko je ovo safe), a i sam mail koji ti je i inace unique key u bazi, pa da ga kasnije preko toga identifikujes... Al ajd kad sam vec krenuo da se pravim pametan, ostavicu. P.s. istripovo me komentar od Kshishtof na https://www.5balloons.info/user-email-verification-and-account-activation-in-laravel-5-5/
      	]);
       
        // Stara registracija bez email verifikacije, gde se odmah nakon registracije user loguje:
       //  $token = $user->createToken('Login token')->accessToken;
       //  \Auth::login($user);
       //  $logedUser = \Auth::user();
      	// return response()->json(compact('token', 'logedUser'));

        // Nova registracija sa mail verifikacijom
        \Mail::to($createdUser)->send(new RegisterVerification($createdUser)); 
        $createdUserName = $createdUser->first_name;

        return response()->json(compact('createdUserName'));
        
    }

    public function registerWithProfileImage(Request $request){

        // Laravel prilikom ajax zahteva automatski vraca greske u json formatu, a prilikom regularnog http zahteva pravi redirekciju msm na home stranicu i tamo bi trebalo da su ti dostupni errori u json formatu!

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'min:8', 'regex:~[0-9]~', 'confirmed'],
            'password_confirmation' => ['required', 'min:8', 'regex:~[0-9]~'],
            'accepted_terms' => 'required|accepted',
            // Nazalos base64 string (format u kojem se nalazi kropovana slika) se ne tretira kao image tako da ova validacija ne prolazi
            // 'profile_image' => 'image'
            // Umesto nje stavljam ovo (Ovo je validacija pomocu takozvanih Rules, to imas najlepse objasnjeno ovde https://laracasts.com/series/whats-new-in-laravel-5-5/episodes/7 , a imas i u dokumentaciji):
            'profile_image' => new IsBase64Png
        ]);


        $createdUser = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'verify_token' => str_random(25).strval(time()) //dodajem ovde time zbog mogucnosti da se dvojici ili vise usera dostavi isti token ako koristim samo str_random. Kad bi imali isti token, postojala bi mogucnost, da kasnije pri verifikaciji ti trazis usera u bazi po tokenu koji ti je poslao i da ti upit vrati vise usera! I stas onda?! Mada realno, ti mozes slati i id usera preko mail-a (ne znam kolko je ovo safe), a i sam mail koji ti je i inace unique key u bazi, pa da ga kasnije preko toga identifikujes... Al ajd kad sam vec krenuo da se pravim pametan, ostavicu. P.s. istripovo me komentar od Kshishtof na https://www.5balloons.info/user-email-verification-and-account-activation-in-laravel-5-5/
        ]);

        if($request->profile_image){
            $profileImage = $request->profile_image;
            $uploadedImagesFolder = 'http://127.0.0.1:8000/uploaded-images/profile-images/';
            
            // Ovaj base64_decode je standardan nacin da dekodiras base64 string u sliku, a ovaj preg_replace imas pre svega ovde https://ourcodeworld.com/articles/read/76/how-to-save-a-base64-image-from-javascript-with-php (inace jedan od prvih rezultata na google) a i ovde istu stvar https://laracasts.com/discuss/channels/laravel/insert-base64-decoded-image-in-database
            $decodedProfileImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $profileImage));
            $generatedName = $this->get_clean_microtimestamp_string().str_random(30).'.png';
            
            // Ideju pokupio ovde https://laracasts.com/discuss/channels/laravel/create-folder-inside-public-path-when-uploading-images-l52 . Inace mora ovako da se uradi, jer metoda file_put_contents ne moze da napravi novi folder, samo moze da prebacuje (za razliku od move metode, ali ona se moze koristiti samo za file a ne za dekodirani base64 string, vidi https://stackoverflow.com/questions/48792585/decode-and-move-base64-encoded-image-in-laravel). PS ovo je standardni nacin da se u Laravelu kreiraju folderi (http://laravel-recipes.com/recipes/147/creating-a-directory), mada izgleda u pozadini koriste php metodu mkdir (https://stackoverflow.com/questions/21869223/create-folder-in-laravel) koju si verovatno i ti mogao koristiti komotno.
            if (!\File::exists(public_path('uploaded-images/profile-images/'.$createdUser->id))) {
                \File::makeDirectory(public_path('uploaded-images/profile-images/'.$createdUser->id));
            }
            file_put_contents(public_path('uploaded-images/profile-images/'.$createdUser->id.'/'.$generatedName), $decodedProfileImage);
            

            $createdUser->profile_image = $uploadedImagesFolder.$createdUser->id.'/'.$generatedName;
            $createdUser->save();
        }

        
        
        // Mail verifikacija
        \Mail::to($createdUser)->send(new RegisterVerification($createdUser)); 
        $createdUserName = $createdUser->first_name;

        return response()->json(compact('createdUserName'));
       
       


       // $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->profile_image)); 

       // $pera = $request->profile_image;
       // return response()->json(compact('pera')); 
    }

    public function getUserInfo()
    {
        $loggedUser = \Auth::user();
        return $loggedUser;
    }

    public function updateUserData(Request $request)
    {
        $loggedUser = \Auth::user();
        /*$request->validate([
            // Ovaj sometimes validator je jedna strava stvar, pomocu nje kazes da je ovo polje ponekad-sometimes prisutno, pa ako je prisutno onda primeni ostala validaciona pravila. Dakle, primeni mi validaciona pravila jedino ako je polje prisutno u requestu ("In some situations, you may wish to run validation checks against a field only if that field is present in the input array. To quickly accomplish this, add the  sometimes rule to your rule list": https://laravel.com/docs/5.5/validation#conditionally-adding-rules). A pomocu not_in validatora mu kazes da vrednost polja ne moze biti jednaka nekoj vrednosti i msm da je ovo jako bitan validator.
            'first_name' => 'sometimes|required|not_in:'.$loggedUser->first_name,
            'last_name' => 'sometimes|required|not_in:'.$loggedUser->last_name,
            // Ovde prvo stavljam not_in pa unique da mi ne vraca gresku email has been taken, ako je korisnik uneo istu vrednost svog maila
            'email' => 'sometimes|required|email|not_in:'.$loggedUser->email.'|unique:users',
            'password' => ['sometimes', 'required', 'min:8', 'regex:~[0-9]~', 'confirmed'],
            // Ovaj required_with znaci da ovo polje mora biti prisutno samo ako je i password polje prisutno, pa mu onda i ne moras stavljati onaj sometimes, mada msm da moze i ovako i onako. Zapravo, sa sometimes i required u paru mu zapravo kazes da ako postoji ovo polje u requestu ono ne moze da bude prazno, a sa required_with:password mu kazes da ukoliko postoji password polje, mora postojati i on sam i ne moze biti prazan. Msm jebavanje cisto, stavljaj sta oces, cisto da vidis da mozes oba. 
            'password_confirmation' => ['required_with:password', 'min:8', 'regex:~[0-9]~'],
            // Nazalos base64 string (format u kojem se nalazi kropovana slika) se ne tretira kao image tako da ova validacija ne prolazi
            // 'profile_image' => 'image'
            // Umesto nje stavljam ovo (Ovo je validacija pomocu takozvanih Rules, to imas najlepse objasnjeno ovde https://laracasts.com/series/whats-new-in-laravel-5-5/episodes/7 , a imas i u dokumentaciji):
            'profile_image' => ['sometimes', 'required', new IsBase64Png]
        ]);*/

        // Umesto validate metodde direktno na requestu, radim ovaj Validator::make, zato jer mogu kao poslednji argument da mu prosledim custom error poruke, i msm da je to mozda i najbolji nacin da ih definises, svi drugi nacini mi se cine ili dosta komplikovanijim ili moras na globalnom nivou da definises te poruke za celu aplikaciju... Imas to sve na dokumentaciji za validaciju
        \Validator::make($request->all(), [
            // Ovaj sometimes validator je jedna strava stvar, pomocu nje kazes da je ovo polje ponekad-sometimes prisutno, pa ako je prisutno onda primeni ostala validaciona pravila. Dakle, primeni mi validaciona pravila jedino ako je polje prisutno u requestu ("In some situations, you may wish to run validation checks against a field only if that field is present in the input array. To quickly accomplish this, add the  sometimes rule to your rule list": https://laravel.com/docs/5.5/validation#conditionally-adding-rules). A pomocu not_in validatora mu kazes da vrednost polja ne moze biti jednaka nekoj vrednosti i msm da je ovo jako bitan validator (pokupio odavde: https://stackoverflow.com/questions/34842487/laravel-5-2-validation-check-if-value-is-not-equal-to-a-variable , ostatak vidi na dokumentaciji).
            'first_name' => 'sometimes|required|not_in:'.$loggedUser->first_name,
            'last_name' => 'sometimes|required|not_in:'.$loggedUser->last_name,
            // Ovde prvo stavljam not_in pa unique da mi ne vraca gresku email has been taken, ako je korisnik uneo istu vrednost svog maila
            'email' => 'sometimes|required|email|not_in:'.$loggedUser->email.'|unique:users',
            'password' => ['sometimes', 'required', 'min:8', 'regex:~[0-9]~', 'confirmed'],
            'password_confirmation' => ['sometimes', 'required', 'min:8', 'regex:~[0-9]~'],
            // Ovaj required_with znaci da ovo polje mora biti prisutno samo ako je i neko od navedenih polja prisutno. 
            'reentered_password' => ['required_with:first_name, last_name, email, password, password_confirmation'],
            // Nazalos base64 string (format u kojem se nalazi kropovana slika) se ne tretira kao image tako da ova validacija ne prolazi
            // 'profile_image' => 'image'
            // Umesto nje stavljam ovo (Ovo je validacija pomocu takozvanih Rules, to imas najlepse objasnjeno ovde https://laracasts.com/series/whats-new-in-laravel-5-5/episodes/7 , a imas i u dokumentaciji):
            'profile_image' => ['sometimes', 'required', new IsBase64Png]

        ], [
            // Ovde ti ovaj :attribute oznacava ime polja nad kojim se vrsi validacija. Spisak ovakvih stvari nisam nasao, ali na dokumentaciji za validaciju, imas primere u poglavlju (valjda su smatrali da su te info dovoljne) https://laravel.com/docs/5.6/validation#working-with-error-messages pa samo trazi niz $messages sa porukama. PS postoji nesto od ovoga i u fajlu resources/lang/en/validation.php
            'not_in' => 'You cannot enter the same value for :attribute.'
        ])->validate();//Kolko sam skontao iz dokumentacije ovaj validate() na kraju se navodi da bi iskoristio osobine te metode, a to je ili da automatski radi redirekciju na home kad koristis laravel i kao frontend, a kad ga koristis samo kao api, u tom slucaju ova funkcija automatski vraca error poruke u vidu JSON-a

        if($request->reentered_password){
            // Ovo se msm ne moze uraditi u unutar validacije, pa radim ovako posebno. Hteo sam sa "in" pravilom unutar validacije ovo da resim (in validira polje ukoliko je ono jednako nekoj vrednosti), ali ovo je nemoguce uraditi zato sto se hashovane sifre iz baze ne mogu vracati nikad u prvobitno stanje (https://laracasts.com/discuss/channels/laravel/how-to-decrypt-hash-password-in-laravel?page=1), pa zbog toga i ne mozes da uporedis sifre preko "in" pravila. Umesto toga koristi se \Hash::check, a objasnjenja za to pogledaj u login kontroleru u funkciji authenticate.
            if(!\Hash::check($request->reentered_password, $loggedUser->password)){
                return response()->json(['error'=>'You provided invalid password!'], 401);
            }
        } 

        // Pomocu ovoga ces pokupiti sve inpute, da bi kasnije kroz njih iterirao. Ovo je potrebno jer se unutar $request nalaze i neki drugi podaci, ovako izdvajam samo inpute, odnosno polja forme
        // $requestInputData = $request->all();
        // OVAKO JE BOLJE, TJ MORAO BI OVAKO DA RADIS!!! Daklem, izdvajam samo ona polja forme koja dolaze u obzir da ih updateujem! Msm da je ovo JAKO bitno, jer tebi zlonamerni korisnik moze da doda koje hoce polje u formu i da je posalje preko requesta! S ovim ovde ti vrsis restrikciju koja ces polja primiti! Bez ovoga npr korisnik bi mogao da ti doda input tipa verified=0 i to bi po meni ovde moglo da prodje da ne vrsis ovakvu restrikciju!!! PS ovu metodu only imas ovde https://laravel.com/docs/5.6/requests
        $requestInputData = $request->only(['first_name', 'last_name', 'email', 'password', 'profile_image']);

        if($requestInputData){
            foreach ($requestInputData as $key => $value) {
               
                if($key === 'profile_image'){
                    $profileImage = $request->profile_image;
                    $uploadedImagesFolder = 'http://127.0.0.1:8000/uploaded-images/profile-images/';
                    
                    // Ovaj base64_decode je standardan nacin da dekodiras base64 string u sliku, a ovaj preg_replace imas pre svega ovde https://ourcodeworld.com/articles/read/76/how-to-save-a-base64-image-from-javascript-with-php (inace jedan od prvih rezultata na google) a i ovde istu stvar https://laracasts.com/discuss/channels/laravel/insert-base64-decoded-image-in-database
                    $decodedProfileImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $profileImage));
                    $generatedName = $this->get_clean_microtimestamp_string().str_random(30).'.png';
                    
                    // Ideju pokupio ovde https://laracasts.com/discuss/channels/laravel/create-folder-inside-public-path-when-uploading-images-l52 . Inace mora ovako da se uradi, jer metoda file_put_contents ne moze da napravi novi folder, samo moze da prebacuje (za razliku od move metode, ali ona se moze koristiti samo za file a ne za dekodirani base64 string, vidi https://stackoverflow.com/questions/48792585/decode-and-move-base64-encoded-image-in-laravel). PS ovo je standardni nacin da se u Laravelu kreiraju folderi (http://laravel-recipes.com/recipes/147/creating-a-directory), mada izgleda u pozadini koriste php metodu mkdir (https://stackoverflow.com/questions/21869223/create-folder-in-laravel) koju si verovatno i ti mogao koristiti komotno.
                    if (!\File::exists(public_path('uploaded-images/profile-images/'.$loggedUser->id))) {
                        \File::makeDirectory(public_path('uploaded-images/profile-images/'.$loggedUser->id));
                    }else{
                        // Ako je prethodno postojao folder koji se zove po korisnikovom id-u i ako je prethodno postojala profil slika u njemu ovde je brisem. Da se ne bi jebavao i brisao sad posebno bas tu sliku, brisem sve fajlove iz foldera, mada ocekujem da ce tu biti samo jedna slika, al ipak brisem sve iz korisnikovog foldera. Ovde to radim na pure PHP nacin (pokupio ovde https://stackoverflow.com/questions/4594180/deleting-all-files-from-a-folder-using-php), mada se ovo moze u uraditi i na laravelov nacin (http://laravel-recipes.com/recipes/150/emptying-a-directory-of-all-files-and-folders)
                        $filesThatExistInFolder = glob(public_path('uploaded-images/profile-images/'.$loggedUser->id).'/*'); // get all file names
                        if($filesThatExistInFolder){
                            foreach($filesThatExistInFolder as $file){ // iterate files
                              if(is_file($file))
                                unlink($file); // delete file
                            }
                        }
                        
                    }
                    file_put_contents(public_path('uploaded-images/profile-images/'.$loggedUser->id.'/'.$generatedName), $decodedProfileImage);
                    

                    $loggedUser->profile_image = $uploadedImagesFolder.$loggedUser->id.'/'.$generatedName;
                }else if($key === 'password'){
                    $loggedUser[$key] = bcrypt($value);
                }else{
                    $loggedUser[$key] = $value;
                }
            }
            
            $loggedUser->save();
            // $filesThatExistInFolder = glob(public_path('uploaded-images/profile-images/'.$loggedUser->id).'/*');
            // $pera = $request['firstName'];
            // return response()->json(compact('pera'));
            return $loggedUser;
        }else{
            return response()->json(['error'=>'You didnt provide any data!'], 422);
        }

        

    }

    public function verify(Request $request)
    {
        $user = User::where(['email'=>$request->email, 'verify_token'=>$request->verify_token])->first();
        if($user){
            // $user->verified = 1;
            // $user->verify_token = NULL;
            // $user->save();
            // MOZE SE URADITI I OVAKO:
            $user->update(['verified'=>1, 'verify_token'=>NULL]);
            $userName = $user->first_name;
            return response()->json(compact('userName'));
        }else{
            return response()->json(['error'=>'Your request is not exceptable to the server. Please try to log in to check if you have already verified your account.'], 406);
        }
        

    }

    //  microtime() metoda u php-u je korisna kad hoces da pravis unique stringove, jedino sto je zajebano je sto ona ne vraca string od uzastopnih brojeva, vec tu ima i razmaka i tacaka, a to bas i pozeljno kad pravis unique stringove, a resenje za taj problem sam nasao ovde:  http://softkube.com/blog/generating-unique-microseconds-granular-timestamps-php (downloadovano > file: Generating Unique Microseconds Granular Timestamps with PHP _ SOFTKUBE) + PS vazno( http://php.net/manual/en/function.microtime.php ): microtime() returns the current Unix timestamp with microseconds. This function is only available on operating systems that support the gettimeofday() system call. Ovo koliko vidim ne vazi za time() funkciju, ona je izgleda svugde dostupna... (http://php.net/manual/en/function.time.php)

    public function get_clean_microtimestamp_string() {
        //Get raw microtime (with spaces and dots and digits)
        $mt = microtime();
        
        //Remove all non-digit (or non-integer) characters
        $r = "";
        $length = strlen($mt);
        for($i = 0; $i < $length; $i++) {
            if(ctype_digit($mt[$i])) {
                $r .= $mt[$i];
            }
        }
        
        //Return
        return $r;
    }
}
