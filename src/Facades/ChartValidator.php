<?php

namespace RiftLab\ImmanuelChart\Facades;

use Illuminate\Support\Facades\Facade;

class ChartValidator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \RiftLab\ImmanuelChart\ChartValidator::class;
    }
}
