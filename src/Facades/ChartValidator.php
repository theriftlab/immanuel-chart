<?php

namespace Sunlight\ImmanuelChart\Facades;

use Illuminate\Support\Facades\Facade;

class ChartValidator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Sunlight\ImmanuelChart\ChartValidator::class;
    }
}
