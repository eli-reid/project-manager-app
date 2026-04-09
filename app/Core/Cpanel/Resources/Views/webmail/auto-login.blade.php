<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to Webmail...</title>
</head>
<body>
<form id="webmail-login" method="POST" action="{{ $loginUrl }}">
    <input type="hidden" name="session" value="{{ $session }}">
</form>
<script>document.getElementById('webmail-login').submit();</script>
</body>
</html>
