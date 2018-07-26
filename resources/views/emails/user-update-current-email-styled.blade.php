@component('mail::message')

# Dear {{$user_first_name}},

You are receiving this email because we received a request to change your account data.

To confirm changes you have requested please click here (changes will NOT be applied if you dont click on this link):<br>
<a href="http://localhost:4200/my-account/verification/{{$user_id}}/{{$token}}">http://localhost:4200/my-account/verification/{{$user_id}}/{{$token}}</a>


If you did not make this request, we HIGHLY recommend that you change your password, because there is a possibility that your account has been compromised. If you need additional help, please contact us.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
