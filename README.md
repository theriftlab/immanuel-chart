# Immanuel Chart

Immanuel Chart provides classes and facades for the passing, validating and retrieving of data between a Lumen project and a bundled Python script that uses the [Flatlib library](https://github.com/flatangle/flatlib/). Currently the Python script is included as a standalone file in this package's repo.

## Installation

```bash
composer require theriftlab/immanuel-chart
```

## Usage
* `Chart::create()` takes an array of birth data - date, time, location, and house system - and returns an instance of itself.
* `getNatalChart()` returns natal chart data based on the array passed to `create()`.
* `getSolarReturnChart($year)` returns solar return chart data for the given year.
* `getProgressedChart($date)` returns progression chart data for the given date.

All the `get` methods above return a Laravel collection containing data for planets and points, including placements and aspects.

You may validate incoming input against the required birth chart data by using `Chart::validate()`. This accepts an array of inputs and the type(s) of validation required. It then returns an instance of a standard Laravel `Validator` for you to query.

Valid house systems, input field names, and validation types are defined in the `ChartValidator` class.

### Example

```php
use RiftLab\ImmanuelChart\Facades\Chart;

...

// Birth details to initialise the chart
$requestInputs = [
    'latitude' => '38.5616505',
    'longitude' => '-121.5829968',
    'birth_date' => '2000-10-30',
    'birth_time' => '05:00',
    'house_system' => 'Polich Page',
];

// Validate - no type passed assumes type "chart"
if (Chart::validate($requestInputs)->passes()) {
    $natalChartData = Chart::create($requestInputs)->getNatalChart();
}

// Add a solar return year
$requestInputs['solar_return_year'] = '2025';

// Validate all - or we could pass "solar" only since "chart" already validated
if (Chart::validate($requestInputs, 'chart', 'solar')->passes()) {
    $solarChartData = Chart::create($requestInputs)->getSolarReturnChart($requestInputs['solar_return_year']);
}

// Add a progression date
$requestInputs['progression_date'] = '2020-07-01';

// Validate just the date
if (Chart::validate($requestInputs, 'progressed')->passes()) {
    $progressedChartData = Chart::create($requestInputs)->getProgressedChart($requestInputs['progression_date']);
}
```
