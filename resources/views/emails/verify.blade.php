    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
</head>
<body>
    <h1>Email Verification</h1>
    <p>Hello {{ $user->full_name }},</p>
    <p>Please click the link below to verify your email address:</p>
    <a href="{{ route('verification.verify', ['token' => $token]) }}">Verify Email</a>
</body>
</html>
