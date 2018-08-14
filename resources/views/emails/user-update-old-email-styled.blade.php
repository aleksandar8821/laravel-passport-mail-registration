@component('mail::message')

# Dear {{$user_first_name}},

You are receiving this email because we received a request to change your account data. You requested an email change, therefore we are presuming that you might not have access to this email anymore, so you are not required to confirm account changes here via your old (this) email, only via your new email.

If you did not make this request there is a possibility that your account has been compromised. However, if you did make this request and typed in wrong new email we also recommend to perform the following action, because anybody who has access to account email can do harm to your account. You can block the request and revoke any changes made to your profile data (ie your email, password etc, NOT changes made to the application itself like making galleries, writting comments etc) by clicking on this link: <br>
<a href="http://localhost:4200/my-account/block_revoke_changes/{{$user_id}}/{{$user_update_id}}/{{$token}}">http://localhost:4200/my-account/block_revoke_changes/{{$user_id}}/{{$user_update_id}}/{{$token}}</a>. Clicking on this link will also log you out everywhere where you were logged in till now, so if anyone has illegaly entered your account, he will lose access to it. Your account will also get blocked for next 48 hours, and only you will be able to access it through a safe link that we will provide you. We HIGHLY recommend that you change your password for security reasons.

If you need additional help, please contact us.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
