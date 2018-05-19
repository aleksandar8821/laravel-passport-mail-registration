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
            // Umesto nje stavljam ovo:
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

        
        
        // Nova registracija sa mail verifikacijom
        \Mail::to($createdUser)->send(new RegisterVerification($createdUser)); 
        $createdUserName = $createdUser->first_name;

        return response()->json(compact('createdUserName'));
       
       


       // $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->profile_image)); 

       // $pera = $request->profile_image;
       // return response()->json(compact('pera')); 
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
