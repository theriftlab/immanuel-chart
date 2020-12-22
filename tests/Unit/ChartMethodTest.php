<?php

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use RiftLab\ImmanuelChart\Tests\TestCase;
use RiftLab\ImmanuelChart\Facades\Chart;

class ChartMethodTest extends TestCase
{
    use ArraySubsetAsserts;

    /**
     * Data for assertions.
     *
     */
    protected $natalDataSubset = [
        'planets' => [
            'sun' => [
                'sign' => 'Scorpio',
            ],
            'moon' => [
                'sign' => 'Sagittarius',
            ],
        ],
    ];

    protected $solarReturnDataSubset = [
        'planets' => [
            'sun' => [
                'sign' => 'Scorpio',
            ],
            'moon' => [
                'sign' => 'Aquarius',
            ],
        ],
    ];

    protected $progressionDataSubset = [
        'planets' => [
            'sun' => [
                'sign' => 'Scorpio',
            ],
            'moon' => [
                'sign' => 'Virgo',
            ],
        ],
    ];

    protected $transitDataSubset = [
        'planets' => [
            'sun' => [
                'sign' => 'Cancer',
            ],
            'moon' => [
                'sign' => 'Aries',
            ],
        ],
    ];

    protected $natalAspectsToSolarReturnDataSubset = [
        'planets' => [
            'sun' => [
                'aspects' => [
                    'moon' => [
                        'type' => 'Square',
                    ]
                ],
            ],
        ],
    ];

    protected $natalAspectsToTransitsDataSubset = [
        'planets' => [
            'sun' => [
                'aspects' => [
                    'moon' => [
                        'type' => 'Quincunx',
                    ]
                ],
            ],
        ],
    ];

    /**
     * Test addNatalChart() data.
     *
     * @return void
     */
    public function testNatalChartData()
    {
        $natalChartData = Chart::create($this->chartDetails)
            ->addNatalChart()
            ->get();

        $this->assertArraySubset($this->natalDataSubset, $natalChartData);
    }

    /**
     * Test addSolarReturnChart() data.
     *
     * @return void
     */
    public function testSolarReturnChartData()
    {
        $solarChartData = Chart::create($this->chartDetails)
            ->addSolarReturnChart($this->solarReturnYear)
            ->get();

        $this->assertArraySubset($this->solarReturnDataSubset, $solarChartData);
    }

    /**
     * Test addProgressedChart() data.
     *
     * @return void
     */
    public function testProgressedChartData()
    {
        $progressedChartData = Chart::create($this->chartDetails)
            ->addProgressedChart($this->progressionDate)
            ->get();

        $this->assertArraySubset($this->progressionDataSubset, $progressedChartData);
    }

    /**
     * Test double chart chaining data.
     *
     * @return void
     */
    public function testDoubleChartData()
    {
        $chartData = Chart::create($this->chartDetails)
            ->addProgressedChart($this->progressionDate)
            ->addSolarReturnChart($this->solarReturnYear)
            ->get();

        $this->assertArrayHasKey('primary', $chartData);
        $this->assertArrayHasKey('secondary', $chartData);
        $this->assertArraySubset($this->progressionDataSubset, $chartData['primary']);
        $this->assertArraySubset($this->solarReturnDataSubset, $chartData['secondary']);
    }

    /**
     * Test transit data.
     *
     * @return void
     */
    public function testTransitData()
    {
        $chartData = Chart::create($this->chartDetails)
            ->addNatalChart()
            ->addTransits($this->transitDate, $this->transitTime)
            ->get();

        $this->assertArrayHasKey('primary', $chartData);
        $this->assertArrayHasKey('transits', $chartData);
        $this->assertArraySubset($this->natalDataSubset, $chartData['primary']);
        $this->assertArraySubset($this->transitDataSubset, $chartData['transits']);
    }

    /**
     * Test double chart chaining data with transits.
     *
     * @return void
     */
    public function testDoubleChartWithTransitsData()
    {
        $chartData = Chart::create($this->chartDetails)
            ->addNatalChart()
            ->addSolarReturnChart($this->solarReturnYear)
            ->addTransits($this->transitDate, $this->transitTime)
            ->get();

        $this->assertArrayHasKey('primary', $chartData);
        $this->assertArrayHasKey('secondary', $chartData);
        $this->assertArrayHasKey('transits', $chartData);
        $this->assertArraySubset($this->natalDataSubset, $chartData['primary']);
        $this->assertArraySubset($this->solarReturnDataSubset, $chartData['secondary']);
        $this->assertArraySubset($this->transitDataSubset, $chartData['transits']);
    }

    /**
     * Test double chart chaining data with transits, with aspects to secondary chart and transits.
     *
     * @return void
     */
    public function testDoubleChartWithTransitsMixedAspectsData()
    {
        $chart = Chart::create($this->chartDetails)
            ->addNatalChart()
            ->addSolarReturnChart($this->solarReturnYear)
            ->addTransits($this->transitDate, $this->transitTime);

        $chartData = $chart
            ->aspectsToSolarReturn()
            ->get();

        $this->assertArraySubset($this->natalAspectsToSolarReturnDataSubset, $chartData['primary']);

        $chartData = $chart
            ->aspectsToTransits()
            ->get();

        $this->assertArraySubset($this->natalAspectsToTransitsDataSubset, $chartData['primary']);
    }

    /**
     * Test exceptions for requesting aspects to nonexistent charts.
     *
     * @return void
     */
    public function testSolarReturnAspectException()
    {
        $chart = Chart::create($this->chartDetails)->addNatalChart();
        $this->expectException(\Exception::class);
        $chartData = $chart->aspectsToSolarReturn()->get();
    }

    public function testProgressedAspectException()
    {
        $chart = Chart::create($this->chartDetails)->addNatalChart();
        $this->expectException(\Exception::class);
        $chartData = $chart->aspectsToProgressed()->get();
    }

    public function testTransitsAspectException()
    {
        $chart = Chart::create($this->chartDetails)->addNatalChart();
        $this->expectException(\Exception::class);
        $chartData = $chart->aspectsToTransits()->get();
    }

    /**
     * Test exception for requesting nonexistent chart.
     *
     * @return void
     */
    public function testGetDataException()
    {
        $this->expectException(\Exception::class);
        $chartData = Chart::create($this->chartDetails)->get();
    }
}
