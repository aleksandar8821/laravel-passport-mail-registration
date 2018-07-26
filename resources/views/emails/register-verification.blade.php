<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title></title>
</head>
<body>
	<p>Welcome to afgalleries.com {{$createdUser->first_name}}! To complete your registration click on this link <a href="http://127.0.0.1:4200/register/verification/{{$createdUser->email}}/{{$createdUser->verify_token}}">http://127.0.0.1:4200/register/verification/{{$createdUser->email}}/{{$createdUser->verify_token}}</a></p>
</body>
</html>