<?php

namespace RiftLab\ImmanuelChart\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Arbitrary chart details for consistent testing.
     *
     */
    protected $chartDetails = [
        'birth_date' => '2000-10-30',
        'birth_time' => '05:00',
        'latitude' => '38.5616505',
        'longitude' => '-121.5829968',
        'house_system' => 'Polich Page',

        'solar_return_year' => '2025',
        'progression_date' => '2021-07-01',

        'synastry_date' => '2001-02-16',
        'synastry_time' => '06:00',
        'synastry_latitude' => '38.5616505',
        'synastry_longitude' => '-121.5829968',

        'transit_date' => '2021-07-01',
        'transit_time' => '13:00',
    ];
}
