<?php

namespace RiftLab\ImmanuelChart;

use Illuminate\Support\Facades\Cache;
use RiftLab\ImmanuelChart\Facades\ChartValidator;
use Symfony\Component\Process\Process;

class Chart
{
    /**
     * Store the basic minimum options required for creating a natal chart.
     * This array is defined by create() and editable with a setter.
     *
     */
    protected $options;

    /**
     * Store required args to send to Python script so we can chain methods.
     * This array is entirely internal and won't be exposed to the user.
     *
     */
    protected $scriptArgs;

    /**
     * Set up by storing options.
     *
     */
    public function create(array $options)
    {
        $this->options = array_intersect_key($options, [
            'latitude' => '',
            'longitude' => '',
            'birth_date' => '',
            'birth_time' => '',
            'house_system' => '',
        ]);

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
     * Add relevant data to script args for a natal chart.
     *
     */
    public function addNatalChart()
    {
        $this->addChart('natal');
        return $this;
    }

    /**
     * Add relevant data to script args for a solar return chart.
     *
     */
    public function addSolarReturnChart(int $year, float $latitude = null, float $longitude = null)
    {
        $this->addChart('solar');
        $this->scriptArgs['solar_return_year'] = $year ?? date('Y');

        if ($latitude && $longitude) {
            $this->scriptArgs += [
                'solar_return_latitude' => $latitude,
                'solar_return_longitude' => $longitude,
            ];
        }

        return $this;
    }

    /**
     * Add relevant data to script args for a progressed chart.
     *
     */
    public function addProgressedChart(string $date, float $latitude = null, float $longitude = null)
    {
        $this->addChart('progressed');
        $this->scriptArgs['progression_date'] = $date ?? date('Y-m-d');

        if ($latitude && $longitude) {
            $this->scriptArgs += [
                'progression_latitude' => $latitude,
                'progression_longitude' => $longitude,
            ];
        }

        return $this;
    }

    /**
     * Add relevant data to script args to append a transit chart.
     *
     */
    public function addTransits(string $date = null, string $time = null, float $latitude = null, float $longitude = null)
    {
        $this->scriptArgs += [
            'with_transits' => 'true',
            'transit_date' => $date ?? date('Y-m-d'),
            'transit_time' => $time ?? date('H:i:s'),
        ];

        if ($latitude && $longitude) {
            $this->scriptArgs += [
                'transit_latitude' => $latitude,
                'transit_longitude' => $longitude,
            ];
        }

        return $this;
    }

    /**
     * Main chart aspects to solar return chart.
     *
     */
    public function aspectsToSolarReturn()
    {
        if (!in_array('solar', $this->scriptArgs)) {
            throw new \Exception('No solar return chart to aspect to.');
        }

        $this->scriptArgs['aspects'] = 'secondary';
        return $this;
    }

    /**
     * Main chart aspects to progressed chart.
     *
     */
    public function aspectsToProgressed()
    {
        if (!in_array('progressed', $this->scriptArgs)) {
            throw new \Exception('No progressed chart to aspect to.');
        }

        $this->scriptArgs['aspects'] = 'secondary';
        return $this;
    }

    /**
     * Main chart aspects to transit chart.
     *
     */
    public function aspectsToTransits()
    {
        if (!isset($this->scriptArgs['with_transits'])) {
            throw new \Exception('No transits to aspect to.');
        }

        $this->scriptArgs['aspects'] = 'transits';
        return $this;
    }

    /**
     * Send script args to script & return chart data from chained methods.
     *
     */
    public function get()
    {
        if (empty($this->options)) {
            throw new \Exception('No base chart options specified.');
        }

        if (!isset($this->scriptArgs['type'])) {
            throw new \Exception('No chart type(s) specified.');
        }

        $scriptArgs = $this->options + $this->scriptArgs;
        return $this->getChartData($scriptArgs);
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
     * Add a chart to the args - either as a primary if one doesn't exist,
     * or as a secondary if a primary does already exist.
     *
     */
    protected function addChart(string $type)
    {
        $key = isset($this->scriptArgs['type']) ? 'secondary_type' : 'type';
        $this->scriptArgs[$key] = $type;
    }

    /*
     * Retreive cached chart data here, or generate if not cached.
     *
     */
    protected function getChartData(array $scriptArgs)
    {
        $key = base64_encode(json_encode($scriptArgs));

        return Cache::remember($key, 60*60*24, function () use ($scriptArgs) {
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
