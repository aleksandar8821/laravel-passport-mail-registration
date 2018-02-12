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
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
            $logedUser = Auth::user();
            $token =  $logedUser->createToken('Login token')->accessToken;

            return response()->json(compact('token', 'logedUser'), 200);
        }
        else{
            return response()->json(['error'=>'Unauthorised'], 401);
        }
 

    }
}
