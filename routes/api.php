<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::get('/user', function () {
// 		$a = auth()->user();
//     // return Auth::user();
//     return response()->json(compact('a'));
// });

// Route::get('/galleries', 'GalleryController@index')->middleware('auth:api');

Route::get('/galleries', 'GalleryController@index');
Route::get('galleries/{id}', 'GalleryController@show');

//Pazi, tebi je autentifikovani user, dostupan ocigledno u onim api rutama sa middlewareom, dok u onim gde ga nemas, nece biti ocigledno ni pokrenuta sesija sa ulogovanim userom!!!
Route::post('/galleries', 'GalleryController@store')->middleware('auth:api');
Route::post('/galleries_multiple_images', 'GalleryController@store_multiple_images')->middleware('auth:api');
Route::post('/gallery_comment', 'GalleryCommentController@store')->middleware('auth:api');
Route::delete('/gallery_comment/{id}', 'GalleryCommentController@destroy')->middleware('auth:api');

Route::post('/login', 'LoginController@authenticate');
Route::post('/register', 'RegisterController@register');

Route::patch('/register/verify', 'RegisterController@verify');
