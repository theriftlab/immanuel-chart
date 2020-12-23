<?php

use Illuminate\Support\Arr;
use RiftLab\ImmanuelChart\Tests\TestCase;
use RiftLab\ImmanuelChart\Facades\Chart;

class ChartValidationTest extends TestCase
{
    /**
     * Test validate() doesn't fail.
     *
     */
    public function testValidatePasses()
    {
        $validator = Chart::validate($this->chartDetails, 'chart');
        $this->assertTrue($validator->passes());
    }
    public function testValidateDefaultPasses()
    {
        $validator = Chart::validate($this->chartDetails);
        $this->assertTrue($validator->passes());
    }

    public function testValidateSolarReturnPasses()
    {
        $validator = Chart::validate($this->chartDetails + ['solar_return_year' => $this->solarReturnYear], 'chart', 'solar');
        $this->assertTrue($validator->passes());
    }

    public function testValidateProgressedPasses()
    {
        $validator = Chart::validate($this->chartDetails + ['progression_date' => $this->progressionDate], 'chart', 'progressed');
        $this->assertTrue($validator->passes());
    }

    public function testValidateSynastryPasses()
    {
        $validator = Chart::validate($this->chartDetails + $this->synastryChartDetails, 'chart', 'synastry');
        $this->assertTrue($validator->passes());
    }

    public function testValidateExtraArrayPasses()
    {
        $validator = Chart::validate($this->chartDetails + ['progression_date' => $this->progressionDate], ['chart', 'progressed']);
        $this->assertTrue($validator->passes());
    }

    public function testValidateExtraOnlyPasses()
    {
        $validator = Chart::validate(['solar_return_year' => $this->solarReturnYear], 'solar');
        $this->assertTrue($validator->passes());
    }


    /**
     * Test validate() fails on missing data.
     * In this case we remove the latitude key.
     *
     * @return void
     */
    public function testValidateMissingInputFailure()
    {
        $chartDetails = Arr::except($this->chartDetails, 'latitude');
        $validator = Chart::validate($chartDetails);
        $this->assertTrue($validator->fails());
    }

    /**
     * Test validate() fails on missing extra data.
     *
     * @return void
     */
    public function testValidateMissingSolarInputFailure()
    {
        $validator = Chart::validate($this->chartDetails, 'chart', 'solar');
        $this->assertTrue($validator->fails());
    }

    public function testValidateMissingProgressedInputFailure()
    {
        $validator = Chart::validate($this->chartDetails, 'chart', 'progressed');
        $this->assertTrue($validator->fails());
    }

    /**
     * Test validate() fails with malformed data.
     * In this case we pass an incorrectly formatted transit date.
     *
     * @return void
     */
    public function testValidateBadInputFailure()
    {
        $optionalDetails = ['transit_date' => '30/10/2000'];
        $validator = Chart::validate($optionalDetails, 'optional');
        $this->assertTrue($validator->fails());
    }

    /**
     * Test validate() passes with house_system in any case.
     *
     * @return void
     */
    public function testHouseSystemCaseInsensitivity()
    {
        $chartDetails = ['house_system' => 'POLICH page'] + $this->chartDetails;
        $validator = Chart::validate($chartDetails, 'chart');
        $this->assertTrue($validator->passes());
    }
}
