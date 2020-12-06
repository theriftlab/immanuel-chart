<?php

namespace RiftLab\ImmanuelChart\Facades;

use Illuminate\Support\Facades\Facade;

class Chart extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \RiftLab\ImmanuelChart\Chart::class;
    }
}
