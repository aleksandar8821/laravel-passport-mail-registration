<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Mail\RegisterVerification;

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

    public function verify(Request $request)
    {
        $user = User::where(['email'=>$request->email, 'verify_token'=>$request->verify_token])->first();
        if($user){
            // $user->verified = 1;
            // $user->verify_token = NULL;
            // MOZE SE URADITI I OVAKO:
            $user->update(['verified'=>1, 'verify_token'=>NULL]);
            $userName = $user->first_name;
            return response()->json(compact('userName'));
        }else{
            return response()->json(['error'=>'Your request is not exceptable to the server. Please try to log in to check if you have already verified your account.'], 406);
        }
        

    }
}
