<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title></title>
</head>
<body>
	<p>Welcome to afgalleries.com {{$createdUser->first_name}}! To complete your registration click on this <a href="http://127.0.0.1:4200/register/verification/{{$createdUser->email}}/{{$createdUser->verify_token}}">link</a></p>
</body>
</html>