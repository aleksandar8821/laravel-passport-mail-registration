@component('mail::message')

# Dear {{$user_first_name}},

in next 48 hours you will have access to your account ONLY with the following link. After this period you will be able to access your account regularly.

<a href="http://localhost:4200/safe-login/{{$allow_access_token}}">http://localhost:4200/safe-login/{{$allow_access_token}}</a>


If you need additional help, please contact us.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
