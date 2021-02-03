<?php

namespace RiftLab\ImmanuelChart;

use RiftLab\ImmanuelChart\Facades\ChartValidator;
use Symfony\Component\Process\Process;

class Chart
{
    /**
     * Store the options for generating charts, usually from a POST request.
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
     * Set up by storing options. These should have been checked by the
     * ChartValidator class before being fed into here.
     *
     */
    public function create(array $options)
    {
        $this->options = $options;
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
    public function addSolarReturnChart()
    {
        $this->addChart('solar');
        return $this;
    }

    /**
     * Add relevant data to script args for a progressed chart.
     *
     */
    public function addProgressedChart()
    {
        $this->addChart('progressed');
        return $this;
    }

    /**
     * Add relevant data to script args for a synastry chart.
     * This will always be a secondary chart.
     *
     */
    public function addSynastryChart()
    {
        $this->addChart('synastry');
        return $this;
    }

    /**
     * Add relevant data to script args to append a transit chart.
     *
     */
    public function addTransits()
    {
        if (!isset($this->scriptArgs['type'])) {
            throw new \Exception('No chart type(s) specified.');
        }

        $this->scriptArgs['with_transits'] = 'true';
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
     * Main chart aspects to synastry chart.
     *
     */
    public function aspectsToSynastry()
    {
        if ($this->scriptArgs['secondary_type'] !== 'synastry') {
            throw new \Exception('No synastry chart to aspect to.');
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
    public function get($forcePrimaryOnSingleChart = false)
    {
        if (empty($this->options)) {
            throw new \Exception('No base chart options specified.');
        }

        if (!isset($this->scriptArgs['type'])) {
            throw new \Exception('No chart type(s) specified.');
        }

        if ($forcePrimaryOnSingleChart) {
            $this->scriptArgs['force_primary_chart_key'] = 'true';
        }

        return $this->getChartData($this->options + $this->scriptArgs);
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
        if (isset($this->scriptArgs['secondary_type'])) {
            throw new \Exception('Only two non-transit charts may be returned.');
        }
        elseif (!isset($this->scriptArgs['type'])) {
            if ($type === 'synastry') {
                throw new \Exception('No primary chart defined.');
            }
            $this->scriptArgs['type'] = $type;
        }
        else {
            $this->scriptArgs['secondary_type'] = $type;
        }
    }

    /**
     * Generate the requested chart here.
     * Currently this uses the chart.py script, but could potentially aggregate
     * data from several sources. It assumes all input has been validated as
     * chart.py will not perform its own validation.
     *
     */
    protected function getChartData(array $scriptArgs)
    {
        // Assemble command-line arguments
        $cmdScriptArgs = [];

        foreach ($scriptArgs as $key => $value) {
            $cmdScriptArgs[] = "--{$key}=$value";
        }

        // Run script
        $scriptPath = realpath(__DIR__.'/Python/chart.py');
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
