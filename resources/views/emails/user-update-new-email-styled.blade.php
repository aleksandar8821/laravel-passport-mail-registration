@component('mail::message')

# Dear {{$user_first_name}},

You are receiving this email because we received a request to change your account data.

To confirm changes you have requested please click here:<br>
<a href="http://localhost:4200/my-account/verification/{{$user_id}}/{{$token}}">http://localhost:4200/my-account/verification/{{$user_id}}/{{$token}}</a>


If you did not make this request, we apologize for the inconvenience, please ignore this mail.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
