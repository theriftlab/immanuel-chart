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
        $validator = Chart::validate($this->chartDetails, 'natal');
        $this->assertTrue($validator->passes());
    }
    public function testValidateDefaultPasses()
    {
        $validator = Chart::validate($this->chartDetails);
        $this->assertTrue($validator->passes());
    }

    public function testValidateSolarReturnPasses()
    {
        $validator = Chart::validate($this->chartDetails, 'natal', 'solar');
        $this->assertTrue($validator->passes());
    }

    public function testValidateProgressedPasses()
    {
        $validator = Chart::validate($this->chartDetails, 'natal', 'progressed');
        $this->assertTrue($validator->passes());
    }

    public function testValidateSynastryPasses()
    {
        $validator = Chart::validate($this->chartDetails, 'natal', 'synastry');
        $this->assertTrue($validator->passes());
    }

    public function testValidateExtraAsArrayPasses()
    {
        $validator = Chart::validate($this->chartDetails, ['natal', 'progressed']);
        $this->assertTrue($validator->passes());
    }

    public function testValidateExtraOnlyPasses()
    {
        $validator = Chart::validate(['solar_return_year' => $this->chartDetails['solar_return_year']], 'solar');
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
        $chartDetails = Arr::except($this->chartDetails, 'solar_return_year');
        $validator = Chart::validate($chartDetails, 'natal', 'solar');
        $this->assertTrue($validator->fails());
    }

    public function testValidateMissingProgressedInputFailure()
    {
        $chartDetails = Arr::except($this->chartDetails, 'progression_date');
        $validator = Chart::validate($chartDetails, 'natal', 'progressed');
        $this->assertTrue($validator->fails());
    }

    public function testValidateMissingSynastryInputFailure()
    {
        $chartDetails = Arr::except($this->chartDetails, 'synastry_latitude');
        $validator = Chart::validate($chartDetails, 'natal', 'synastry');
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
        $validator = Chart::validate($chartDetails, 'natal');
        $this->assertTrue($validator->passes());
    }
}
