<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\UserAccessBlocking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{

    // hmmm, da, kao sto vidis ni u vivify nije radjena logout metoda, tako da valjda ne treba https://gitlab.com/vivify-ideas/vivifyacademy-api-be/blob/master/app/Http/Controllers/Auth/LoginController.php

    public function authenticate(Request $request)
    {
     /*
     * login api
     *
     * @return \Illuminate\Http\Response
     */ 
     
        $user = User::where(['email'=>$request->email])->first();

        /***KOD ZA BLOKIRANJE PRISTUPA BLOKIRANIM USERIMA (u ovom konkretnom slucaju moram da proveravam da li user postoji tj radim odma na pocetku if($user) (na ostalim mestima gde sam dosada radio ovakvu blokadu ne treba ovo), jer ukoliko ne postoji $user, desice se greska u ovom delu koda za blokiranje tako da kod ispod koji radi autentifikaciju se nece ni izvrsiti. Ovim if($user) je to reseno!)***/
        if($user){
            $userBlocked = UserAccessBlocking::where('user_id', $user->id)->where('expires_at', '>', now())->first();

            if($userBlocked){
                if ($request->allow_access_token !== $userBlocked->allow_access_token) {

                    if($userBlocked->expires_at){
                        $unblockPeriod = Carbon::now()->diffInHours(Carbon::createFromFormat('Y-m-d H:i:s', $userBlocked->expires_at));
                        
                        if($unblockPeriod > 1) {
                            return response()->json(['error'=>'Your account is blocked, and you will not be able to access it for next '.$unblockPeriod.' hours!'], 403);
                        }else{
                            $unblockPeriod = Carbon::now()->diffInMinutes(Carbon::createFromFormat('Y-m-d H:i:s', $userBlocked->expires_at));
                            return response()->json(['error'=>'Your account is blocked, and you will not be able to access it for next '.$unblockPeriod.' minutes!'], 403);
                        }

                    }else{
                        return response()->json(['error'=>'Your account is blocked!'], 403);
                    }
                    
                }
            }
        }
        /****************************************************/

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
}
