<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserAccessBlocking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Mail\PasswordResetStyled;

class PasswordResetController extends Controller
{
    public function forgotPasswordRequest(Request $request)
    {

		// exists proverava dal postoji user sa datim mailom (https://laravel.com/docs/5.6/validation#rule-exists)
		$request->validate([
			'email' => 'required|email|exists:users'
		]);

		$email = $request->email;
		$user = User::where('email', $email)->firstOrFail(); //s ovim ga jos jednom prakticno proveravam, mada i nema potrebe jer je gore vec jednom provereno, al, paranoicno, za svaki slucaj :)

        /***KOD ZA BLOKIRANJE PRISTUPA BLOKIRANIM USERIMA***/

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

        /****************************************************/

        // Onemogucavam da korisnici koji nisu verifikovali svoj account da menjaju sifru, mada se ovo mozda i moze dopustiti... Nisam siguran, al ajd za svaki slucaj da uradim i to...
        if($user->verified === 0){
            return response()->json(["error" => "You are not authorized for this action, because your account is not yet verified! Please verify your account via email that we sent you, when you registered."], 401);
        }

		$user_first_name = $user->first_name;
		$token = str_random(40).time(); //str_random() preporucen ovde https://laracasts.com/discuss/channels/laravel/implementing-reset-password-feature-without-makeauth , ipak dodajem time da ne dodje do duplikata

		\Mail::to($email)->send(new PasswordResetStyled($email, $user_first_name, $token));



		// koristio princip https://laravel.com/docs/5.5/queries#inserts
		DB::table('password_resets')->insert(
			['email' => $email, 'token' => $token, 'created_at' => now()]
		);
		// Kolko vidim isti je rezultat, koristio 'created_at' => now() (vidi https://laravel.com/docs/5.5/helpers#method-now , https://stackoverflow.com/questions/48020234/how-to-insert-a-value-to-created-at-field-using-query-builder sto je isto kolko shvatam ko i ovo https://stackoverflow.com/questions/33452124/query-builder-not-inserting-timestamps) ili 'created_at' => date('Y-m-d H:i:s') (vidi https://laracasts.com/discuss/channels/general-discussion/created-at-and-updated-at-never-set-on-insert)

        // Ako sam prethodno imao password reset request od istog usera, brisem ga iz baze, jer sta ce mi? Neko moze i iz zle namere npr. da ti puni bazu sa requestovima mada tesko, al ajd... Mislim da je bolje da brises nepotrebne podatke, a i savetuje se npr ovde u najboljem odgovoru https://laracasts.com/discuss/channels/laravel/implementing-reset-password-feature-without-makeauth. Ovo radim na samom kraju kad sam siguran da je novi zahtev upisan u bazu i da mogu da brisem stari (PISEM OVO KASNIJE: OVAJ PRINCIP JE PO MOM MISLJENJU DOSTA DOBAR ZA RAZLIKU KAD BI NPR STAVIO U BAZI DA TI JE EMAIL UNIQUE (KO STO SAM RADIO SA USER UPDATEOVIMA - DODUSE STAVLJAO SAM TAMO UNIQUE NA USER_ID, AL SVEJEDNO). TAD NE BI MOGAO DA PRVO UNESES NOVI ZAHTEV, PA TEK ONDA DA BRISES STARI!). I naravno moram da budem siguran da necu obrisati novouneseni pa dodajem uslov where('token', '!=', $token). Inace ovaj != se preporucuje po vebu i meni radi posao, ali se spominje i <>, vidi po odgovorima: https://stackoverflow.com/questions/23260171/laravel-operator-in-where-not-working , https://stackoverflow.com/questions/28256933/eloquent-where-not-equal-to. Za brisanje koristim https://laravel.com/docs/5.6/queries#deletes
        DB::table('password_resets')->where('email', $request->email)->where('token', '!=', $token)->delete(); //where('token', '!=', $token) se moze napisati i sa where('token', 'not like', '%'.$token.'%') vidi npr. https://stackoverflow.com/questions/39247338/filter-out-multiple-where-not-like

        return response()->json(["success" => "success"], 200);
    }



    public function resetPassword(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users',
            'token' => 'required',
            'password' => ['required', 'min:8', 'regex:~[0-9]~', 'confirmed'],
            'password_confirmation' => ['required', 'min:8', 'regex:~[0-9]~']
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        /***KOD ZA BLOKIRANJE PRISTUPA BLOKIRANIM USERIMA***/

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

        /****************************************************/

        // Ponovo (mozda bespotrebno) proveravam da li je user koji hoce da menja sifru verifikovao svoj nalog prilikom registracije, pa ako nije brisem mu i zahtev za promenu sifre (mada sam ga gore u funkciji forgotPasswordRequest onemogucio i da ga unese ako nije verifikovan, al ajd) i stopiram promenu sifre
        if($user->verified === 0){
            DB::table('password_resets')->where('email', $request->email)->where('token', $request->token)->delete();

            return response()->json(["error" => "You are not authorized for this action, because your account is not yet verified! Please verify your account via email that we sent you, when you registered."], 401);
        }

        // Proveravam da li postoji kombinacija emaila i tokena
    	$emailAndToken = DB::table('password_resets')->where([
            'email' => $request->email,
            'token' => $request->token       
        ])->first();

        if($emailAndToken){
            // return response()->json(compact('emailAndToken'));
            
            // Ovo radim po ugledu na Laravelove kontrolere za password resete koji ti daju fore da uradis reset sat vremena, vidi crveno upozorenje https://laravel.com/docs/5.6/passwords#after-resetting-passwords

            // Opet koristim Carbon metodu createFromFormat() jer se addHour() mora izvrsiti (mislim da mora, u primerima u dokumentaciji radi sa ovim formatom) nad DateTime objektom koji uostalom i createFromFormat() vraca (ne stoji ti direktno u dokumentaciji da ovo vraca, ali ova metoda se zasniva na http://php.net/manual/en/datetime.createfromformat.php koja vraca DateTime objekat)
            // Inace addHour() i greaterThan() su isto carbon metode (http://carbon.nesbot.com/docs/) 
            if(now()->greaterThan((Carbon::createFromFormat('Y-m-d H:i:s', $emailAndToken->created_at))->addHour())){
                return response()->json(["error" => "Your permission to reset your password has expired! To reset your password, please send us your email again."], 401);
            }

            // $datum = Carbon::createFromFormat('Y-m-d H:i:s', $emailAndToken->created_at);
            // $datum = \DateTime::createFromFormat('Y-m-d H:i:s', $emailAndToken->created_at);
            // $datum = now();
            // $datum = new \DateTime();
            // Sva ova cetri iznad vracaju php DateTime objekat koji izgleda: {"datum":{"date":"2018-04-08 14:56:51.000000","timezone_type":3,"timezone":"UTC"}}
            // return response()->json(compact('datum'));

            // Menjam sifru:
            $user->password = bcrypt($request->password);
            $user->save();

            // Logujem usera
            \Auth::login($user, true);
            $logedUser = \Auth::user();
            $loginToken =  $logedUser->createToken('Login token')->accessToken;

            // Brisem request za promenu sifre iz baze
            DB::table('password_resets')->where('email', $request->email)->where('token', $request->token)->delete();

            return response()->json(compact('loginToken', 'logedUser'), 200);

        }else{
            return response()->json(["error" => "You are not authorized for this action!"], 401);
        }
    }

    
}
