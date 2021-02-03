<?php

namespace RiftLab\ImmanuelChart;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChartValidator
{
    /**
     * Chart's validation rules.
     *
     */
    protected $rules;

    /**
     * House systems accepted by Flatlib.
     * These are lowercased for validation since the script
     * itself now applies the correct case required by FlatLib.
     *
     */
    protected $houseSystems = [
        'alcabitus',
        'azimuthal',
        'campanus',
        'equal 2',
        'equal',
        'koch',
        'meridian',
        'morinus',
        'placidus',
        'polich page',
        'porphyrius',
        'regiomontanus',
        'vehlow equal',
        'whole sign',
    ];

    /**
     * New instance - set up all validation rules here.
     *
     */
    public function __construct()
    {
        $this->rules = [
            'natal' => [
                'latitude' => ['required', 'numeric'],
                'longitude' => ['required', 'numeric'],
                'birth_date' => ['required', 'date_format:Y-m-d'],
                'birth_time' => ['required', 'date_format:H:i'],
                'house_system' => ['required', 'string', Rule::in($this->houseSystems)],
            ],
            'solar' => [
                'solar_return_year' => ['required', 'regex:/[0-9]{4}/'],
            ],
            'progressed' => [
                'progression_date' => ['required', 'date_format:Y-m-d'],
            ],
            'synastry' => [
                'synastry_date' => ['required', 'date_format:Y-m-d'],
                'synastry_time' => ['required', 'date_format:H:i'],
                'synastry_latitude' => ['required', 'numeric'],
                'synastry_longitude' => ['required', 'numeric'],
            ],
            'optional' => [
                'solar_return_latitude' => 'numeric',
                'solar_return_longitude' => 'numeric',
                'progression_latitude' => 'numeric',
                'progression_longitude' => 'numeric',
                'transit_latitude' => 'numeric',
                'transit_longitude' => 'numeric',
                'transit_date' => 'date_format:Y-m-d',
                'transit_time' => 'date_format:H:i',
            ],
        ];
    }

    /**
     * Validate the provided chart details here.
     *
     */
    public function validate(array $inputs, ...$ruleTypes)
    {
        $ruleTypes = !empty($ruleTypes) ? Arr::flatten($ruleTypes) : ['natal'];
        $rules = Arr::collapse(Arr::only($this->rules, $ruleTypes));

        if (!empty($inputs['house_system'])) {
            $inputs['house_system'] = strtolower($inputs['house_system']);
        }

        return Validator::make($inputs, $rules);
    }
}
