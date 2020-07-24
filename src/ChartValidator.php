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
     *
     */
    protected $houseSystems = [
        'Placidus',
        'Koch',
        'Porphyrius',
        'Regiomontanus',
        'Campanus',
        'Equal',
        'Equal 2',
        'Vehlow Equal',
        'Whole Sign',
        'Meridian',
        'Azimuthal',
        'Polich Page',
        'Alcabitus',
        'Morinus',
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
        return Validator::make($inputs, $rules);
    }
}
