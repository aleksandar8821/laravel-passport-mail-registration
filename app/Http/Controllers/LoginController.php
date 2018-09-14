<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
// use App\UserAccessBlocking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\My_custom_files\userAccessBlockingTrait;

use Lcobucci\JWT\Parser;


class LoginController extends Controller
{

    // hmmm, da, kao sto vidis ni u vivify nije radjena logout metoda, tako da valjda ne treba https://gitlab.com/vivify-ideas/vivifyacademy-api-be/blob/master/app/Http/Controllers/Auth/LoginController.php

    use userAccessBlockingTrait;


    public function authenticate(Request $request)
    {
     /*
     * login api
     *
     * @return \Illuminate\Http\Response
     */ 
     
        $user = User::where(['email'=>$request->email])->first();
        
        // Ovu metodu check_if_user_is_blocked vucem iz traita userAccessBlockingTrait
        $response = $this->check_if_user_is_blocked($request, $user);
        if ($response) {
            return response()->json(['error' => $response], 403);
        }

        //Ovako inace radi Laravelova metoda za login Auth::attempt()(https://laravel.com/docs/5.5/authentication#authenticating-users), ja sam je razlozio da bi mogao da uhvatim dve razlicite greske kao i da user-a logujem tek nakon pojedinacnih provera za greske, jer attempt() metoda odmah loguje usera. Dakle attempt() prvo vadi user-a preko mail-a pa zatim proverava da li mu se sifra koju je uneo poklapa sa hashovanom sifrom u bazi (vidi check metodu ovde https://laravel.com/docs/5.5/hashing) i odmah ga loguje. Ja sam isto uradio (+ proveravam da li je verified) samo u odvojenim koracima, da bi mogao da hvatam pojedinacne greske:

        if($user && (\Hash::check($request->password, $user->password))){
            if($user->verified === 1){
                Auth::login($user, true);
                $logedUser = Auth::user();

                $token =  $logedUser->createToken('Login token')->accessToken;

                return response()->json(compact('token', 'logedUser'), 200);
            }else{
                return response()->json(['error'=>'Your account is not yet verified, you need to verify it via email that we have sent you!'], 403);
            }
            
            
        }
        else{
            // vidis ovaj 401 je ovde dosta bitan da se navede, kao i 200 gore (mada mi se cini da je ovaj defaultni status i da se ne mora navoditi), zato sto na frontendu angular u subscribe metodi ima dva callback-a sucess i error, i on odlucuje koji ce da aktivira po ovim brojevima!
            return response()->json(['error'=>'You provided invalid credentials, please try again!'], 401);
        }
 

    }

    // Ovu metodu koristim kada radim revoke access (login) tokena, odnosno kada ja namerno izlogujem usera (force logout) svugde gde je ikad bio ulogovan iz nekog razloga (imas ovo u register controleru, samoo trazi $token->revoke()). E sad taj logout se desava samo ovde na backendu, a na frontendu ostaje token u local storage-u i tebi se prikazuje UI takav kao da je korisnik ulogovan a u stvari nije. Da bi to resio, ja pri svakom http zahtevu koji zahteva da se uz njega salju http headeri sa tokenom iz local storage, saljem jos jedan zahtev koji poziva bas ovu funkciju. Pomocu nje saznajem da li je na backednu uradjen force logout, pa ako jeste, radim to i na frontendu (imas funkciju forceLogout u AuthService koja radi maltene iste stvari kao i obican logout) i time resavam problem. Da ovo ne radim na frontendu bi mi se prikazivao stalno UI kao da sam ulogovan iako zapravo nisam, a zahtevi koje bi pravio a koji zahtevaju da budes ulogovan se ne bi izvrsavali, a dobijao bi gresku od backenda koju (trenutno bar) ne znas  kako da hendlujes! Tako da je ovo za sada najbolje resenje. * KAO DODATAK OVA FUNKCIJA INICIRA FORCE LOGOUT NA FRONTENDU UKOLIKO U LOCAL STORAGEU NE POSTOJE TOKEN I EMAIL, ILI UKOLIKO SU ONI POGRESNI!
    public function should_force_logout_be_performed(Request $request)
    {
        // Bolje da validiras sam da bi mogao da preduzmes odgovarajuce akcije ukoliko nesto nije kako treba 
        /*$request->validate([
            'email' => 'required|email|exists:users',
        ]);*/

        // Ako nema tokena ili emaila u requestu, tj ako ih nije ni bilo u local storage-u. Msm da ovo moras prvo ovde da ispitas (da ti bude prvo po redosledu), da bi ti sve radilo kako treba.
        if(!$request->accessToken || !$request->email){
            return response()->json(["message" => "force logout"], 200); 
        }

        $user = User::where('email', $request->email)->first();

        if(!$user){
            return response()->json(["message" => "force logout"], 200);
        }

        // $token = $user->token;//Ovo ti nije dostupno ovde ovako, jedino izgleda ovako https://stackoverflow.com/questions/46673667/laravel-passport-api-retrieve-authenticated-token >>> dakle moras da si ulogovan, tj da ti na rutu bude zakacen auth middleware, ko sto se cak i u linku dole u jednom postu spominje, dok su ti npr $user->tokens uvek dostupni. Zasto je to tako ne znam ali je tako.

        // Nakon 1000 godina resenje nasao ovde https://laracasts.com/discuss/channels/laravel/passport-how-can-i-manually-revoke-access-token
        $value = $request->accessToken;
        // Ovo ce da logoutuje usera ukoliko je sam editovao token u local storage-u, jer ovaj Parser odma prepoznaje da on nije u formi u kojoj treba da bude i baca gresku koju sam ja ovde uspeo da uvatim (gledas u konzoli kad ti baci gresku error -> exception) i iniciram naravno force logout na frontendu
        try {
            $id= (new Parser())->parse($value)->getHeader('jti');
        } catch (\RuntimeException $e) {
            return response()->json(["message" => "force logout"], 200); 
        }

        $token= $user->tokens->find($id);

        // Naravno ukoliko se token ne nalazi u userovim tokenima i tu je opet nesto sumnjivo i iniciram force logout na frontu
        if(!$token){
            return response()->json(["message" => "force logout"], 200); 
        }

        if($token->revoked){
            return response()->json(["message" => "force logout"], 200); 
        }else{
            return response()->json(["message" => 'token not revoked'], 200); 
        }
        
    }

}
