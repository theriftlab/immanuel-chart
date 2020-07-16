<?php

use Illuminate\Support\Arr;
use Sunlight\ImmanuelChart\Tests\TestCase;
use Sunlight\ImmanuelChart\Facades\Chart;

class ChartValidationTest extends TestCase
{
    /**
     * Test validate() doesn't fail.
     *
     * @return void
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

    public function testValidateExtraPasses()
    {
        $validator = Chart::validate($this->chartDetails + ['solar_return_year' => $this->solarReturnYear], 'chart', 'solar');
        $this->assertTrue($validator->passes());
    }

    public function testValidateExtraArrayPasses()
    {
        $validator = Chart::validate($this->chartDetails + ['solar_return_year' => $this->solarReturnYear], ['chart', 'solar']);
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
    public function testValidateMissingExtraInputFailure()
    {
        $validator = Chart::validate($this->chartDetails, 'chart', 'solar');
        $this->assertTrue($validator->fails());
    }

    /**
     * Test validate() fails with malformed data.
     * In this case we pass an incorrectly formatted birth date.
     *
     * @return void
     */
    public function testValidateBadInputFailure()
    {
        $chartDetails = ['birth_date' => '30/10/2000'] + $this->chartDetails;
        $validator = Chart::validate($chartDetails, 'chart');
        $this->assertTrue($validator->fails());
    }
}
