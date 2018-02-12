<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

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



        $user = User::create([
        	'first_name' => $request->first_name,
        	'last_name' => $request->last_name,
        	'email' => $request->email,
        	'password' => bcrypt($request->password)
      	]);
        
        $token = $user->createToken('Login token')->accessToken;
        \Auth::login($user);
        $logedUser = \Auth::user();
      	return response()->json(compact('token', 'logedUser'));
    }
}
