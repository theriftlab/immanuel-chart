<?php

namespace Sunlight\ImmanuelChart\Facades;

use Illuminate\Support\Facades\Facade;

class Chart extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Sunlight\ImmanuelChart\Chart::class;
    }
}
