@component('mail::message')

# Dear {{$user_first_name}},

You are receiving this email because we received a request to change your account data.

To confirm changes you have requested please click here (changes will NOT be applied if you dont click on this link):<br>
<a href="http://localhost:4200/my-account/verification/{{$user_id}}/{{$verify_token}}">http://localhost:4200/my-account/verification/{{$user_id}}/{{$verify_token}}</a>

If you did not make this request there is a possibility that your account has been compromised. Therefore we recommend that you click on the link below which will permanently block the data change request, it will also log you out everywhere where you were logged in till now, so if anyone has illegaly entered your account, he will lose access to it. Your account will also get blocked for next 48 hours, and only you will be able to access it through a safe link that we will provide you. Also, if you have accidentally confirmed changes not requested by you, you will be able to revoke them using this link and rollback to the state of your account data that existed in the moment this mail was sent (note that this feature does not work if you have already used it in an older email and if this action was made when this email that you are currently reading already existed in your inbox, i.e. you can only rollback backwards between existing states, never forwards). If you have any doubts that your account has been compromised, we HIGHLY recommend that you change your password for security reasons, and we recommend doing it in mentioned 48 hours. <br>
<a href="http://localhost:4200/my-account/block_revoke_changes/{{$user_id}}/{{$user_update_id}}/{{$block_request_token}}">http://localhost:4200/my-account/block_revoke_changes/{{$user_id}}/{{$user_update_id}}/{{$block_request_token}}</a>. 

If you need additional help, please contact us.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
