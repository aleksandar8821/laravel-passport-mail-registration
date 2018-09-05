@component('mail::message')

# Dear {{$user_first_name}},

You are receiving this email because we received a request to change your account data.

To confirm changes you have requested please click here (changes will NOT be applied if you dont click on this link):<br>
<a href="http://localhost:4200/my-account/verification/{{$user_id}}/{{$verify_token}}">http://localhost:4200/my-account/verification/{{$user_id}}/{{$verify_token}}</a>

If you did not make this request there is a possibility that your account has been compromised. Therefore we recommend that you click on the link below which will permanently block the account data change request, it will also log you out everywhere where you were logged in till now, so if anyone has illegaly entered your account, he will also be logged out. Your account will also get blocked for next 48 hours, and only you will be able to access it through a safe link that we will provide you. If you have any doubts that your account has been compromised, we HIGHLY recommend that you change your password for security reasons, and we recommend doing it in mentioned 48 hours. <br>
<a href="http://localhost:4200/my-account/block_request_and_account_logout_user/{{$user_id}}/{{$block_request_token}}">http://localhost:4200/my-account/block_request_and_account_logout_user/{{$user_id}}/{{$block_request_token}}</a>. 

If you need additional help, please contact us.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
