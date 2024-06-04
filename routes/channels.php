<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('buzz.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('noti-payment.user.{id}', function ($user, $id) {
    return $user->id == $id ? true : false;
});
Broadcast::channel('buzz.calendar.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('shared-private-notification', function ($user) {
    // You can define your own authorization logic here if needed
    return true; // All users are allowed to join this channel
});
