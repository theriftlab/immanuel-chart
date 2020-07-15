<?php

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Sunlight\ImmanuelChart\Tests\TestCase;
use Sunlight\ImmanuelChart\Facades\Chart;

class ChartMethodTest extends TestCase
{
    use ArraySubsetAsserts;

    /**
     * Test getNatalChart() doesn't fail.
     *
     * @return void
     */
    public function testNatalChart()
    {
        $natalChartData = Chart::create($this->chartDetails)->getNatalChart();
        $this->assertNotFalse($natalChartData);
    }

    /**
     * Test getSolarReturnChart() doesn't fail.
     *
     * @return void
     */
    public function testSolarReturnChart()
    {
        $solarChartData = Chart::create($this->chartDetails)->getSolarReturnChart($this->solarReturnYear);
        $this->assertNotFalse($solarChartData);
    }

    /**
     * Test getNatalChart() data.
     *
     * @return void
     */
    public function testNatalChartData()
    {
        $natalChartData = Chart::create($this->chartDetails)->getNatalChart();
        $this->assertArraySubset([
            'planets' => [
                'sun' => [
                    'planet' => 'Sun',
                    'sign' => 'Scorpio',
                ],
                'moon' => [
                    'planet' => 'Moon',
                    'sign' => 'Sagittarius',
                ],
            ],
        ], $natalChartData);
    }

    /**
     * Test getSolarReturnChart() data.
     *
     * @return void
     */
    public function testSolarReturnChartData()
    {
        $solarChartData = Chart::create($this->chartDetails)->getSolarReturnChart($this->solarReturnYear);
        $this->assertArraySubset([
            'planets' => [
                'sun' => [
                    'planet' => 'Sun',
                    'sign' => 'Scorpio',
                ],
                'moon' => [
                    'planet' => 'Moon',
                    'sign' => 'Aquarius',
                ],
            ],
        ], $solarChartData);
    }
}
