@component('mail::message')

# Dear {{$user_first_name}},

You are receiving this email because we received a request to change your account data. You requested an email change, therefore we are presuming that you might not have access to this email anymore, so you are not required to confirm account changes here via your old (this) email, only via your new email.

However, if you did not make this request, you can block the request and revoke any changes made to your profile data (ie your email, password etc, NOT changes made to the application itself like making galleries, writting comments etc) by clicking on this link: <br>
<a href="http://localhost:4200/my-account/block_revoke_changes/{{$user_id}}/{{$user_update_id}}/{{$token}}">http://localhost:4200/my-account/block_revoke_changes/{{$user_id}}/{{$user_update_id}}/{{$token}}</a>. If you did not make this request, we also HIGHLY recommend that you change your password, because there is a possibility that your account has been compromised. 

If you need additional help, please contact us.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
