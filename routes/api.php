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

Route::get('/galleries_firebase', 'GalleryController@allGalleriesForFirebase');

//Pazi, tebi je autentifikovani user, dostupan ocigledno u onim api rutama sa middlewareom, dok u onim gde ga nemas, nece biti ocigledno ni pokrenuta sesija sa ulogovanim userom!!!
Route::post('/galleries', 'GalleryController@store')->middleware('auth:api');
Route::post('/galleries_multiple_images', 'GalleryController@store_multiple_images')->middleware('auth:api');
Route::post('/gallery_comment', 'GalleryCommentController@store')->middleware('auth:api');
Route::delete('/gallery_comment/{id}', 'GalleryCommentController@destroy')->middleware('auth:api');
Route::patch('/gallery_comment/update', 'GalleryCommentController@update')->middleware('auth:api');

Route::post('/image_comment', 'ImageCommentController@store')->middleware('auth:api');
Route::delete('/image_comment/{id}', 'ImageCommentController@destroy')->middleware('auth:api');

Route::post('/login', 'LoginController@authenticate');
Route::post('/forgot_password', 'PasswordResetController@forgotPasswordRequest');
Route::post('/password-reset', 'PasswordResetController@resetPassword');
Route::post('/register', 'RegisterController@register');
Route::post('/register_with_profile_image', 'RegisterController@registerWithProfileImage');
Route::get('/get_user_info', 'RegisterController@getUserInfo')->middleware('auth:api');

// U zavisnosti od toga da li su promenjeni samo neki podaci o useru ili svi podaci(doduse bukvalno sva polja iz baze se nece ovde nikada menjati, jer postoje i polja kao sto su verified, zatim tokeni i sl, al nema veze, to mozda i nije bitno, ne znam), koriste se metode patch i put respektivno. Razliku izmedju put i patch requesta vidi ovde https://www.youtube.com/watch?v=-eo3OEW6z1Q (downloadovano), https://laracasts.com/discuss/channels/general-discussion/whats-the-differences-between-put-and-patch?page=1
Route::patch('/update_user_data', 'RegisterController@updateUserData')->middleware('auth:api');
Route::put('/update_user_data', 'RegisterController@updateUserData')->middleware('auth:api');
Route::post('/update_user_data_mail_conf', 'RegisterController@updateUserDataWithMailConfirmation')->middleware('auth:api');
Route::patch('/update_user_data_mail_conf/verify', 'RegisterController@verifyUserUpdate');
Route::patch('/update_user_data_mail_conf/block_revoke_changes', 'RegisterController@blockRevokeChanges');


Route::patch('/register/verify', 'RegisterController@verify');
