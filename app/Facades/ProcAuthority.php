<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class ProcAuthority extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'procAuthority';
    }
}
