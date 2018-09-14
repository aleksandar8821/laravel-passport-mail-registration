<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserUpdate;
use App\UserDataVersion;
use App\UserAccessBlocking;
use App\Mail\RegisterVerification;
use App\Mail\UserUpdateCurrentEmailStyled;
use App\Mail\UserUpdateNewEmailStyled;
use App\Mail\UserUpdateOldEmailStyled;
use App\Mail\SafeAccessStyled;
use App\My_custom_files\userAccessBlockingTrait;
use App\Rules\IsBase64Png;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{

    use userAccessBlockingTrait;


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

    // Ovo je metoda koja updateuje usera, ali bez mail confirmationa i radi savrseno
    public function updateUserData(Request $request)
    {
        $loggedUser = \Auth::user();

        if($request->reentered_password){
            // Ovo se msm ne moze uraditi u unutar validacije, pa radim ovako posebno. Hteo sam sa "in" pravilom unutar validacije ovo da resim (in validira polje ukoliko je ono jednako nekoj vrednosti), ali ovo je nemoguce uraditi zato sto se hashovane sifre iz baze ne mogu vracati nikad u prvobitno stanje (https://laracasts.com/discuss/channels/laravel/how-to-decrypt-hash-password-in-laravel?page=1), pa zbog toga i ne mozes da uporedis sifre preko "in" pravila. Umesto toga koristi se \Hash::check, a objasnjenja za to pogledaj u login kontroleru u funkciji authenticate.
            if(!\Hash::check($request->reentered_password, $loggedUser->password)){
                return response()->json(['error'=>'You provided invalid password!'], 401);
            }
        }


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


    public function updateUserDataWithMailConfirmation(Request $request)
    {
        $loggedUser = \Auth::user();

        if($request->reentered_password){
            // Ovo se msm ne moze uraditi u unutar validacije, pa radim ovako posebno. Hteo sam sa "in" pravilom unutar validacije ovo da resim (in validira polje ukoliko je ono jednako nekoj vrednosti), ali ovo je nemoguce uraditi zato sto se hashovane sifre iz baze ne mogu vracati nikad u prvobitno stanje (https://laracasts.com/discuss/channels/laravel/how-to-decrypt-hash-password-in-laravel?page=1), pa zbog toga i ne mozes da uporedis sifre preko "in" pravila. Umesto toga koristi se \Hash::check, a objasnjenja za to pogledaj u login kontroleru u funkciji authenticate.
            if(!\Hash::check($request->reentered_password, $loggedUser->password)){
                return response()->json(['error'=>'You provided invalid password!'], 401);
            }
        }

        

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
            'reentered_password' => ['required_with:first_name, last_name, email, password, password_confirmation']

        ], [
            // Ovde ti ovaj :attribute oznacava ime polja nad kojim se vrsi validacija. Spisak ovakvih stvari nisam nasao, ali na dokumentaciji za validaciju, imas primere u poglavlju (valjda su smatrali da su te info dovoljne) https://laravel.com/docs/5.6/validation#working-with-error-messages pa samo trazi niz $messages sa porukama. PS postoji nesto od ovoga i u fajlu resources/lang/en/validation.php
            'not_in' => 'You cannot enter the same value for :attribute.'
        ])->validate();//Kolko sam skontao iz dokumentacije ovaj validate() na kraju se navodi da bi iskoristio osobine te metode, a to je ili da automatski radi redirekciju na home kad koristis laravel i kao frontend, a kad ga koristis samo kao api, u tom slucaju ova funkcija automatski vraca error poruke u vidu JSON-a
         

        // Pomocu ovoga ces pokupiti sve inpute, da bi kasnije kroz njih iterirao. Ovo je potrebno jer se unutar $request nalaze i neki drugi podaci, ovako izdvajam samo inpute, odnosno polja forme
        // $requestInputData = $request->all();
        // OVAKO JE BOLJE, TJ MORAO BI OVAKO DA RADIS!!! Daklem, izdvajam samo ona polja forme koja dolaze u obzir da ih updateujem! Msm da je ovo JAKO bitno, jer tebi zlonamerni korisnik moze da doda koje hoce polje u formu i da je posalje preko requesta! S ovim ovde ti vrsis restrikciju koja ces polja primiti! Bez ovoga npr korisnik bi mogao da ti doda input tipa verified=0 il nesto slicno (to bi bio onaj mass assignment sto se spominje u laravel dokumentaciji i laracasts tutorijalu) i to bi po meni ovde moglo da prodje da ne vrsis ovakvu restrikciju!!! PS ovu metodu only imas ovde https://laravel.com/docs/5.6/requests
        $requestInputData = $request->only(['first_name', 'last_name', 'email', 'password']);

        if($requestInputData){
            $userUpdate = new UserUpdate;
            
            foreach ($requestInputData as $key => $value) {
                if($key === 'password'){
                    $userUpdate[$key] = bcrypt($value);
                }else{
                    $userUpdate[$key] = $value;
                }
            }

            $userUpdate->user_id = $loggedUser->id;
            $verify_token = str_random(25).$this->get_clean_microtimestamp_string();
            $userUpdate->verify_token = $verify_token;
            /*if($request->email){
                $block_request_token = $this->get_clean_microtimestamp_string().str_random(30);
                $userUpdate->block_request_token = $block_request_token;
            }*/
            // Ipak stavljam block_request_token i ako ne menjam mail (MOZDA SAMO PRIVREMENO, MOZDA IPAK OVO NE BUDEM KORISTIO VIDECU - ako budes hteo da vratis na staro samo izbrisi ova dva reda ispod i odkomentarisi gore ovaj if + naravno iz current maila izbrisi onda ovaj token) da bi ga koristio u current mailu
            $block_request_token = $this->get_clean_microtimestamp_string().str_random(30);
            $userUpdate->block_request_token = $block_request_token;
            
            
            // $userUpdate->save();

            // Moze dobaviti samo jedan record iz baze, jer si u migraciji naveo da je user_id unique, tako da koristi first() slobodno
            $alreadyExistingRequest = UserUpdate::where('user_id', $loggedUser->id)->first();

            /*Ovaj ceo veliki try catch ovde radim zato sto sam u migraciji stavio da mi user_id bude unique i sad kad pokusam da unesem request od istog usera, doci ce do greske u mysqlu (DA ODMAH NAPRAVIM DISCLAIMER: MISLIM DA JE OVAJ NACIN MOZDA PREVISE KOMPLIKOVAN, AL GA OSTAVLJAM ZBOG POUCNOG KODA. MSM DA JE BOLJE DA RADIS KAO ZA PASSWORD RESETE. VIDI TU MIGRACIJU KOJA INACE DOLAZI UZ INSTALACIJU LARAVELA, TAKO DA BI MORALA BITI PRILICNO POUZDANA. VIDECES U NJOJ DA SE NE PRAVI UNIQUE POLJE ZA EMAIL, A KAKO SPRECAVAS DUPLIKATE VIDI U PASSWORD RESET KONTROLERU U METODI forgotPasswordRequest KAKO RADIS BRISANJE. MSM DA JE TAJ NACIN BOLJI JER MOZES U STARTU DA UNESES U TABELU REQUEST KOJI IMA ISTI EMAIL KAO I NEKI VEC POSTOJECI U TABELI - PA TEK KAD SI SIGURAN DA SI GA UNEO, TAD MOZES KOMOTNO OBRISATI OVAJ VEC POSTOJECI!). E sad, problem je u tome sto da bi uneo novi request stari prethodno treba da obrisem. Ok, to sam mogao i da uradim bez ovog try cathc, mogo sam samo da proverim if($alreadyExistingRequest) pa ako postoji da ga obrisem, ali ova try catch fora gde hvatas gresku br 1062 je dobra kao primer da imas ovde, al msm da nije najjednostavnije resenje. Zapravo msm da je cela ideja da stavljam unique polje tamo gde cu kasnije ipak unositi redove sa jednakom vrednosti tog polja (naravno uz potrebu da onaj vec postojeci red brisem da bi mogao uneti novi) ipak losa. Zasto? Pa zato sto moram da obrisem ovog vec postojeceg, a u principu nemam garancije da ce mi se ovaj novi sigurno uneti u tabelu (mada ce ti se NAJVEROVATNIJE uneti, al ajd), jer moze doci do neke greske. Tada bi dosao u situcaiju da sam obrisao postojeci red u tabeli, a novi nisam uneo. Naravno tu postoji fora da ovog postojeceg pre nego sto ga obrises iz tabele sacuvas u memoriji backend koda sto ja ovde i radim (u try catch unutar ovog glavnog try catch), pa ukoliko unosenje novog ne uspe, onda ipak vracam starog u tabelu (mada ako hoces da sitnicaris mozes postaviti i pitanje, a sta ako ti ni ovaj povratak starog ne uspe? jbg). U ovom tvom nested try catch delu tu moze doci do problema ukoliko unosenje novog ne uspe zbog neke greske koje catch deo ne predvidja da uhvati, onda ti se catch deo nece ni izvrsiti. Moguce je da postoji fora da hvatas bukvalno bilo koju gresku ali gledajuci po netu cini mi se da je to suvise komplikovano, pa cu ipak ostaviti ovako...*/
            // ovu foru sa try catch pokupio ovde https://stackoverflow.com/questions/27878719/laravel-catch-eloquent-unique-field-error
            try{

                $userUpdate->save();

            }catch (\Illuminate\Database\QueryException $e){
                // Ovaj \Illuminate\Database\QueryException ovde koristim jer mi radi posao, ali kad bi se desila neka greska koja ne spada u QueryException ovaj kod unutar catcha ti se uopste ne bi izvrsio, al bi laravel sam bacio gresku (to sam testirao i skontao)
                
                $errorCode = $e->errorInfo[1];
                // vidi ovde za gresku br 1062 https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html#error_er_dup_entry - nju ti salje mysql kada pokusavas da uneses novi red u tabelu koji sadrzi duplikat nekog unique polja, u ovom slucaju ako unosis novi red koji ima isti user_id kao neki postojeci red u tabeli
                if($errorCode === 1062){

                    // Ovde brisem taj postojeci red iz tabele koji sadrzi isti user_id kao ovaj novi koji pokusavam da unesem
                    UserUpdate::where('user_id', $loggedUser->id)->delete();
                    // Kad ga obrisem ponovo pokusavam da unesem ovaj novi red i u slucaju da to iz nekog razloga (sto se verovatno nece desiti) ne uspe, onda ovaj stari koji sam obrisao iz baze, ali sacuvao ovde u Laravel kodu, vracam nazad u bazu sa ovom insert metodom i vracam gresku na frontend zbog koje nisam uspeo da unesem ovaj novi red.
                    try {

                        $userUpdate->save();

                    } catch (\Illuminate\Database\QueryException $e) {
                        // Kad bi ti se ovde desila neka greska koja ne spada u ovaj \Illuminate\Database\QueryException (sto doduse ni ne znam dal moze da se desi, ovde npr kaze da ce sve syntax i query greske u mysqlu biti ovog tipa: https://stackoverflow.com/questions/33679996/how-do-i-catch-a-query-exception-in-laravel-to-see-if-it-fails, a mozda moze docci i do nekih drugih gresaka osim syntax i query prilikom save() metode, ja to ne znam), ovaj catch bi ti se uopste ne bi izvrsio, tako da msm da ovo nije najsigurniji nacin da radis sve ovo, al ajd...Moguce je da postoji fora da hvatas bukvalno bilo koju gresku ali gledajuci po netu cini mi se da je to suvise komplikovano, pa cu ipak ostaviti ovako...

                        // Ovaj insert (https://laravel.com/docs/5.5/queries#inserts) ovde koristim jer hocu da mi ostanu stari timestampsovi (kad koristis query builder oni se ne updateuju automatski https://stackoverflow.com/questions/33452124/query-builder-not-inserting-timestamps), a ako koristim save ili create metodu (Eloquent metode) oni se automatski updateuju
                        DB::table('user_updates')->insert(
                            [
                                'user_id' => $alreadyExistingRequest->user_id,
                                'verify_token' => $alreadyExistingRequest->verify_token,
                                'block_request_token' => $alreadyExistingRequest->block_request_token,
                                'first_name' => $alreadyExistingRequest->first_name,
                                'last_name' => $alreadyExistingRequest->last_name,
                                'email' => $alreadyExistingRequest->email,
                                'password' => $alreadyExistingRequest->password,
                                'created_at' => $alreadyExistingRequest->created_at,
                                'updated_at' => $alreadyExistingRequest->updated_at
                            ]
                        );

                        // Ovo sam sam ovako formirao (nisam nasao na netu il tako nesto), i stavio 500 jer mi je inace laravel vracao mysql gresku za duplikat (1062) sa statusom 500 jbg, pa sam po ugledu na to uradio i radilo mi je ovako, dal moze bolje ne znam
                        return response()->json(['error' => $e], 500);

                    }
                    
                }

            }

            // Ukoliko se vrsi izmena maila
            if ($request->email) {
                \Mail::to($request->email)->send(new UserUpdateNewEmailStyled($request->email, $loggedUser->id, $loggedUser->first_name, $verify_token));
                // Pomocu ovog tokena user moze sa starog maila ili da blokira request ukoliko jos nije odobren sa novog maila, ili ukoliko je odobren da ponisti promene koje su tom prilikom nastale
                $block_request_or_revoke_changes_token = $block_request_token;
                \Mail::to($loggedUser->email)->send(new UserUpdateOldEmailStyled($loggedUser->first_name, $loggedUser->id, $userUpdate->id, $block_request_or_revoke_changes_token));            
            }else{
                \Mail::to($loggedUser->email)->send(new UserUpdateCurrentEmailStyled($loggedUser->email, $loggedUser->id, $loggedUser->first_name, $verify_token, $block_request_token));
            }

            return response()->json(["success" => "success"], 200);
        }else{
            return response()->json(['error'=>'You didnt provide any data!'], 422);
        }

        

    }

    public function verifyUserUpdate(Request $request){
        $user_update_row = UserUpdate::where(['user_id'=>$request->user_id, 'verify_token'=>$request->verify_token])->first();

        if($user_update_row){
            $user = User::find($request->user_id);
            if($user){

                // Kada updateujem usera uvek prethodnu verziju usera sejvujem u tabelu user_data_versions
                $userUpdateVersion = new UserDataVersion;

                $userUpdateVersion->user_id = $user->id;
                $userUpdateVersion->user_update_id = $user_update_row->id;
                $userUpdateVersion->rollback_revoke_changes_token = $user_update_row->block_request_token;
                $userUpdateVersion->first_name = $user->first_name;
                $userUpdateVersion->last_name = $user->last_name;
                $userUpdateVersion->email = $user->email;
                $userUpdateVersion->password = $user->password;

                // $userUpdateVersion->save();

                // Ovde se koristi ova metoda i super radi! https://laravel.com/docs/5.5/collections#method-only
                $user_update_data = $user_update_row->only(['first_name', 'last_name', 'email', 'password']);
                foreach ($user_update_data as $key => $value) {
                    if($value)
                        $user[$key] = $value;
                }

                $userUpdated = $user->save();
                
                if($userUpdated){
                    $userUpdateVersion->save();
                    UserUpdate::where('user_id', $user->id)->delete();

                    // Ukoliko se updateuje email ili sifra (koji su oba login credentials, tj sluze za login) korisnik se svugde automatski logoutuje 
                    if($user_update_row->email || $user_update_row->password){
                        // Ovo logoutuje usera svugde gde je ulogovan, detaljnija objasnjenja ovoga imas ovde u funkciji blockUserWithSafeAccess u traitu userAccessBlockingTrait
                        $userTokens = $user->tokens;

                        foreach($userTokens as $token) {
                            $token->revoke();   
                        }


                        return response()->json(["message" => "force logout"], 200);
                    }
                    
                }


                return $user;
            }else{
                return response()->json(['error'=>"You are not authorized for this action!"], 401);
            }
        }else{
            return response()->json(['error'=>'Your request is not exceptable to the server. Please try to log in to check if you have already verified your account changes. Also if you made account change requests after this request, then this request is not valid anymore.'], 406);
        }
    }

    // Huh, ova metoda se aktivira kada korisnik sumnja da mu je zlonamerni korisnik (otimac naloga) upao na nalog i izvrsio promenu maila (i eventualno i ostalih podataka). Tada ovaj legalni korisnik dobija na svoj originalni (stari) mail poruku sa linkom koji aktivira ovu metodu. Ona treba da blokira request za promenu ukoliko on jos nije potvrdjen od strane otimaca naloga, a ukoliko je potvrdjen treba da vrati podatke na staro, onemoguci otimacu naloga bilo kakav dalji pristup nalogu i pocisti svo djubre koje je on eventualno ostavio na nalogu. Tu mislim na ono djubre koje bi mu omogucilo ponovno otimanje naloga, a ne na akcije koje je uradio na samoj aplikaciji, tipa pisao komentare i slicno. Zasto se tim akcijama ne bavim? Zato sto je on mogao praviti te akcije i pre nego sto je promenuo podatke. Jednostavno ne bi znao od kog momenta da pocnem da ponistavam te akcije, jer ne znam u kom momentu je on poceo da vrslja po tudjem nalogu.
    public function blockRevokeChanges(Request $request)
    {
        $user_id = $request->user_id;
        // Ovaj user_update_id vucem iz tabele user_updates i treba mi da bi red u tabeli user_data_versions mogao sigurno jednoznacno da targetiram. Jeste i da ga id jednoznacno odredjuje, ali meni treba nesto sto ce ga jednoznacno odrediti jos pre nego sto sacuvam taj red u tabeli, da bi ga mogao koristiti u old emailu za rollback (revoke changes kako god) u ovoj metodi
        $user_update_id = $request->user_update_id;
        $block_request_token = $request->block_request_revoke_changes_token;
        $rollback_revoke_changes_token = $request->block_request_revoke_changes_token;

        $request_for_blocking = UserUpdate::where(['user_id'=>$user_id, 'block_request_token'=>$block_request_token])->first();
        
        // Definicija sa google translate za rollback: the process of restoring a database or program to a previously defined state, typically to recover from an error.
        // Ovu restrikciju za NULL ipak stavljam, jer moguce je da mi neki zlonamerni korisnik umesto tokena posalje NULL na server i to bi mozda i moglo da prodje, a to ne bi nikako smelo da prodje, jer verzije uzera kojima je token NULL su one verzije na koje se user ne moze povratiti vise.
        $userRollbackVersion = UserDataVersion::where(['user_id'=>$user_id, 'user_update_id'=>$user_update_id, 'rollback_revoke_changes_token'=>$rollback_revoke_changes_token])->whereNotNull('rollback_revoke_changes_token')->first();
        
        if(!$request_for_blocking && !$userRollbackVersion){
            return response()->json(['error'=>"You are not authorized for this action! Please try to log in to check if you have already restored your account data."], 401);
        }

        if($request_for_blocking){
            $request_for_blocking->delete();
        }

        $user = User::find($user_id);

        if ($userRollbackVersion) {

            // Hah, da, sad mi je palo na pamet, moze se desiti jedan interesantan hack od strane otimaca naloga. Udje ti na nalog promeni ti mail, a zatim posto zna da mozes da uradis rollback na staru verziju podataka, registruje novog usera sa tvojim originalnim mailom. I tada ti ne mozes da uradis rollback, zato sto je email unique u users tabeli! Tako da cu sada to i da sprecim:
            $createdUserForHacking = User::where('email', $userRollbackVersion->email)->where('verified', 0)->first();// Trazim samo korisnika koji nije verifikovan, jer ne moze ga verifikovati, jer nema pristup tvom mailu. A ako i to ima, onda jbg, ima ti pristup svemu onda i onda ne vredi da te spasavam uopste...
            if ($createdUserForHacking) {
                $createdUserForHacking->delete();
            }

            
            // Vracam usera na zahtevanu verziju
            $user->first_name = $userRollbackVersion->first_name;
            $user->last_name = $userRollbackVersion->last_name;
            $user->email = $userRollbackVersion->email;
            $user->password = $userRollbackVersion->password;

            $rolledbackUser = $user->save();

            if($rolledbackUser){
                
                // Kad jednom uradim rollback, taj token pomocu kojeg sam to uradio je iskoriscen i ne moze se vise koristiti, tj vise nema vracanja na tu specificnu verziju, bolje receno taj red u tabeli user_data_versions. PISEM KASNIJE: JEL OVAJ KORAK NEOPHODAN? NE ZNAM, ZA SAD OSTAVLJAM OVAKO...
                $userRollbackVersion->update(['rollback_revoke_changes_token' => NULL]);
                // Takodje kad se uradi rollback na odredjenu verziju usera u tabeli user_data_versions, sve verzije koje su u medjuvremenu nastale posle verzije nad kojom je uradjen rollback gube mogucnost da se na njih vracas. Zasto? Zato sto se predpostavlja da je njih nacinio otimac naloga, a on ima kod sebe u mailovima linkove pomocu kojih se rade rollbackovi na odredjene verzije user podataka. Sledecim korakom se prakticno ti linkovi disableuju, odnosno tokeni koje ti linkovi sadrze postaju nevazeci!
                UserDataVersion::where('user_id', $user_id)->where('created_at', '>', $userRollbackVersion->created_at)->whereNotNull('rollback_revoke_changes_token')->update(['rollback_revoke_changes_token' => NULL]);
                
            }

            // Brisem i bilo kakav nepotvrdjeni request od otimaca naloga (takav moze biti samo jedan, s obzirom da samo jedan request od nekog usera moze biti u tabeli user_updates), ako eventualno postoji tako nesto. Tu se naravno postavlja pitanje zasto ovo ne uraditi odmah u startu i zasto uopste trazis token za brisanje nepotvrdjenog requesta koji sadrzi promenu maila, zasto jednostavno ne das korisniku mogucnost da cim stisne na link za blokadu da se bilo koji nepotvrdjeni request odmah obrise. A odgovor jeeeee: Zato sto bi onda mogao bilo ko bez odgovarajuceg tokena da brise te requestove, a to bi mogao i da iskoristi otimac naloga, nakon sto je nalog povracen legalnom korisniku. Mogao bi u prevodu da brise nepotvrdjene zahteve legalnom korisniku iz svog nekog starog maila koji sadrzi link za blokiranje zahteva. U ovom slucaju to ne moze jer mora da ima odgovarajuci token, koji ce odgovarati verziji u tabeli user_data_versions. Naravno moras ga porediti sa tokenom iz te tabele, a ne sa tokenom iz tabele user_updates, jer ti ovde na pocetku ove metode odmah brises taj request sa tim tokenom iz user_updates tabele, ukoliko on postoji. Ovo vazi dakle za slucaj da request sa tokenom ne postoji u tabeli user_updates, da verzija usera sa tokenom postoji u tabeli user_data_versions, i da u user_updates postoji neki nepotvrdjeni request koji je otimac naloga napravio dok je imao pristup nalogu. Taj request brisem jer u sustini ako se on ne obrise otimac naloga moze da ga potvrdi kad god hoce (i kad se nalog vrati legalnom korisniku) i time da promeni podatke naloga u svoju korist.
            UserUpdate::where('user_id', $user_id)->delete();
            
        }

        //blokiram useru pristup aplikaciji u narednih 48 sati i logoutujem ga svugde gde je dosad bio ulogovan da bi izlogovao i zlonamernog korisnika koji mu je nelegalno pristupio nalogu. Originalnom korisniku saljem mail sa allow access tokenom da bi preko maila mogao da pristupi nalogu - tako da moze pristupiti samo originalni korisnik koji je vlasnik emaila, a ovaj koji mu zloupotrebljava nalog ne moze! Sve to radim preko metode iz traita userAccessBlockingTrait:
        $this->blockUserWithSafeAccess($user, 48);
        
        
        // Po mom misljenju nema potrebe ponistavati password resete koje je otimac naloga eventualno pravio, jer su one uvek vezane za email (na osnovu emaila iz password reset requesta ti selektujes usera kojem ces promenuti sifru), a ti ovde svakako vracas email na staru vrednost, tako da se reset ne moze izvrsiti.

        // Takodje msm da nema potrebe da stavljas kod za blokiranje pristupa na metode za verifikaciju koje se pozivaju preko maila za update za new i current email. Pre svega to ne bi imalo Bog zna kakvog efekta, jer se tim ogranicava neko zlonamerno delovanje samo za 48 sati, a naravno treba to u potpunosti spreciti za sva vremena. A ja sam to zapravo vec i uradio ovde, jer brisem sve zahteve korisnika iz tabele user_updates, tako da je to ovim reseno!
        
        return response()->json(["message" => "force logout"], 200);

    }


    public function block_request_and_account_logout_user(Request $request)
    {
        $user_id = $request->user_id;
        $block_request_token = $request->block_request_token;

        $request_for_blocking = UserUpdate::where(['user_id'=>$user_id, 'block_request_token'=>$block_request_token])->first();

        if($request_for_blocking){
            $request_for_blocking->delete();
        }else{
            return response()->json(['error'=>"You are not authorized for this action!"], 401);   
        }

        $user = User::find($user_id);

        //blokiram useru pristup aplikaciji u narednih 48 sati i logoutujem ga svugde gde je dosad bio ulogovan da bi izlogovao i zlonamernog korisnika koji mu je nelegalno pristupio nalogu. Originalnom korisniku saljem mail sa allow access tokenom da bi preko maila mogao da pristupi nalogu - tako da moze pristupiti samo originalni korisnik koji je vlasnik emaila, a ovaj koji mu zloupotrebljava nalog ne moze! Sve to radim preko metode iz traita userAccessBlockingTrait:
        $this->blockUserWithSafeAccess($user, 48);


        return response()->json(["message" => "force logout"], 200);
        // $loggedUser = \Auth::user();
        // return $loggedUser;
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
