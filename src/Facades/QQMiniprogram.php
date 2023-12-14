<?php

namespace Superzc\QQMiniprogram\Facades;

use Illuminate\Support\Facades\Facade;

class QQMiniprogram extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'qqminiprogram';
    }
}
