@component('mail::message')

# Dear {{$user_first_name}},

You are receiving this email because we received a password reset request for your account.

Click here to reset your password:<br>
<a href="http://localhost:4200/password-reset/{{$email}}/{{$token}}">http://localhost:4200/password-reset/{{$email}}/{{$token}}</a>


If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
