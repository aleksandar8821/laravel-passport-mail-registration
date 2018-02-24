<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function authenticate(Request $request)
    {
     /*
     * login api
     *
     * @return \Illuminate\Http\Response
     */ 
     //Ovako inace radi Laravelova metoda za login Auth::attempt()(https://laravel.com/docs/5.5/authentication#authenticating-users), ja sam je razlozio da bi mogao da uhvatim dve razlicite greske kao i da user-a logujem tek nakon pojedinacnih provera za greske, jer attempt() metoda odmah loguje usera. Dakle attempt() prvo vadi user-a preko mail-a pa zatim proverava da li mu se sifra koju je uneo poklapa sa hashovanom sifrom u bazi (vidi check metodu ovde https://laravel.com/docs/5.5/hashing) i odmah ga loguje. Ja sam isto uradio (+ proveravam da li je verified) samo u odvojenim koracima, da bi mogao da hvatam pojedinacne greske:
     
        $user = User::where(['email'=>$request->email])->first();

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
