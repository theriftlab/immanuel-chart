<?php

namespace Sunlight\ImmanuelChart;

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
     * itself now applies the correct case requried by FlatLib.
     *
     */
    protected $houseSystems = [
        'placidus',
        'koch',
        'porphyrius',
        'regiomontanus',
        'campanus',
        'equal',
        'equal 2',
        'vehlow equal',
        'whole sign',
        'meridian',
        'azimuthal',
        'polich page',
        'alcabitus',
        'morinus',
    ];

    /**
     * New instance - set up all validation rules here.
     *
     */
    public function __construct()
    {
        $this->rules = [
            'chart' => [
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
            ]
        ];
    }

    /**
     * Validate the provided chart details here.
     *
     */
    public function validate(array $inputs, ...$ruleTypes) : \Illuminate\Validation\Validator
    {
        $ruleTypes = !empty($ruleTypes) ? Arr::flatten($ruleTypes) : ['chart'];
        $rules = Arr::collapse(Arr::only($this->rules, $ruleTypes));

        if (!empty($inputs['house_system'])) {
            $inputs['house_system'] = strtolower($inputs['house_system']);
        }

        return Validator::make($inputs, $rules);
    }
}
