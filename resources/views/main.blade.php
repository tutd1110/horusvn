<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" href="/image/1652071497_6.png">
    <title>@yield('title')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
<div id = "app">
    <layout :title="'@yield('title')'">
        @yield('content')
    </layout>
</div>
@vite([ 'resources/js/app.js', 'resources/assets/sass/app.scss'])
</body>
</html>