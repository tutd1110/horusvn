<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- <title>Horus Work</title> -->
    <link rel="icon" href="/image/1652071497_6.png">
    <title>@yield('title')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
<div id = "app">
    <menu-bar></menu-bar>
    <a-layout class="layout">
        <a-layout-content style="padding: 0 50px">
            <bread-crumb></bread-crumb>
            <div :style="{ width: '104.5%', margin: '0px 2% 0px -2.3%' ,background: '#fff', padding: '10px', minHeight: '280px' }">
                @yield('content')
            </div>
        </a-layout-content>
  </a-layout>
</div>
@vite([ 'resources/js/app.js', 'resources/assets/sass/app.scss'])
</body>
</html>