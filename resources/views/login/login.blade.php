<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - Horus Work</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
<div id = "app">
    <a-layout class="layout">
        <a-layout-content>
            <login></login>
        </a-layout-content>
    </a-layout>
</div>
@vite([ 'resources/js/app.js', 'resources/assets/sass/app.scss'])
</body>
</html>