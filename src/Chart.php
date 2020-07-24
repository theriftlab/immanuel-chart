<?php

namespace Sunlight\ImmanuelChart;

use Illuminate\Support\Facades\Cache;
use Sunlight\ImmanuelChart\Facades\ChartValidator;
use Symfony\Component\Process\Process;

class Chart
{
    /**
     * This class will only store the basic minimum data required for creating a
     * natal chart.
     *
     */
    protected $options;

    /**
     * Set up by storing options.
     *
     */
    public function create(array $options)
    {
        $this->options = array_replace([
            'latitude' => '',
            'longitude' => '',
            'birth_date' => '',
            'birth_time' => '',
            'house_system' => '',
        ], $options);

        return $this;
    }

    /**
     * Basic getter for options.
     *
     */
    public function __get($key)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        return null;
    }

    /**
     * Basic setter for options.
     *
     */
    public function __set($key, $value) : void
    {
        if (isset($this->options[$key])) {
            $this->options[$key] = $value;
        }
    }

    /**
     * Validation courtesy of the ChartValidator class.
     *
     */
    public function validate(array $inputs, ...$ruleTypes)
    {
        return ChartValidator::validate($inputs, ...$ruleTypes);
    }

    /**
     * Return a natal chart.
     *
     */
    public function getNatalChart()
    {
        $scriptArgs = $this->options + [
            'type' => 'natal',
        ];
        return $this->getChartData($scriptArgs);
    }

    /**
     * Return a solar return chart.
     *
     */
    public function getSolarReturnChart(int $year)
    {
        $scriptArgs = $this->options + [
            'type' => 'solar',
            'solar_return_year' => $year,
        ];
        return $this->getChartData($scriptArgs);
    }

    /**
     * Return a progressed chart.
     *
     */
    public function getProgressedChart($date = null)
    {
        $scriptArgs = $this->options + [
            'type' => 'progressed',
            'progression_date' => $date ?? date('Y-m-d'),
        ];
        return $this->getChartData($scriptArgs);
    }

    /*
     * Retreive cached chart data here, or generate if not cached.
     *
     */
    protected function getChartData(array $scriptArgs)
    {
        $key = base64_encode(implode($scriptArgs));

        return Cache::rememberForever($key, function () use ($scriptArgs) {
            return $this->generateChartData($scriptArgs);
        });
    }

    /**
     * Generate the requested chart here.
     * Currently this uses the chart.py script, but could potentially aggregate
     * data from several sources. It assumes all input has been validated as
     * chart.py will not perform its own validation.
     *
     */
    protected function generateChartData(array $scriptArgs)
    {
        // Assemble command-line arguments
        $cmdScriptArgs = [];

        foreach ($scriptArgs as $key => $value) {
            $cmdScriptArgs[] = "--{$key}=$value";
        }

        // Run script
        $scriptPath = realpath(__DIR__ . '/Python/chart.py');
        $process = new Process(['python3', $scriptPath, ...$cmdScriptArgs]);
        $process->run();

        // Return data or false on error
        if ($process->isSuccessful()) {
            $output = $process->getOutput();
            $chartData = json_decode($output, true);

            if (json_last_error() === JSON_ERROR_NONE && empty($chartData['error'])) {
                return collect($chartData);
            }
        }

        return false;
    }
}
