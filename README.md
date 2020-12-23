# Immanuel Chart

Immanuel Chart provides classes and facades for the passing, validating and retrieving of data between a Lumen project and a bundled Python script that uses the [Riftlib library](https://github.com/theriftlab/riftlib/). Currently the Python files are included as standalones in this package's repo.

The Chart class allows data for up to three charts to be returned - _natal_, _solar return_, _progressed_ and _synastry_. You may request up to two of these types, and optionally add transits to make it three. Data will be returned as a standard Laravel collection, either as a single array representing the main chart's data if only one chart is requested, or an array of multiple chart data arrays if multiple charts are requested. In this latter case, the keys will be `primary`, `secondary`, and if requested `transits`. You can also specify which chart's planets the main `primary` chart's aspects apply to. By default this will be its own planets as standard, but you can also request its aspects point to the `secondary` or `transit` chart's planets - useful for synastries and transits.

## Installation

```bash
composer require theriftlab/immanuel-chart
```

## Usage

Chart requests are built by chaining methods in a similar way to Eloquent's query builder, starting with the `create()` method which sets up the base natal chart from which to work. You can then add your two charts via the `add...` methods decribed below. The first one called will add the required chart type as the primary chart and any subsequent call will add as the secondary. For example:

```php
Chart::create($chartDetails)->addNatalChart()->addSolarReturnChart('2025');
```

This will add the natal chart as the primary chart, and the solar return as the secondary. You could swap these method calls around and get the solar return chart as the primary and natal as the secondary if you wished.

* `Chart::create(array $options)` takes an array of birth data - `birth_date`, `birth_time`, `latitude`, `longitude`, and `house_system` - and returns an instance of `Chart` for chaining.
* `addNatalChart()` adds the natal chart based on the array passed to `create()`.
* `addSolarReturnChart($year, $latitude, $longitude)` adds a solar return chart for the given year, based on the natal chart data. Optionally, you can pass in new coordinates if you have moved significantly far from your birth place.
* `addProgressedChart($date, $latitude, $longitude)` adds a progression chart for the given date, based on the natal chart data. Again, you can optionally provide new coordinates.
* `addSynastryChart($date, $time, $latitude, $longitude)` adds another natal chart for the given date, time, and coordinates. All arguments are required, and this can only be added as a secondary chart if a primary one has already been added, otherwise an exception will be thrown.
* `addTransits($date, $time, $latitude, $longitude)` adds a transit chart for the given date, time, and coordinates. All arguments are optional, and will default to the current date and time, and the base chart's coordinates from `create()`. Again, an exception will be thrown if there is not at least one chart already added.
* `aspectsToSolarReturn()`, `aspectsToProgressed()`, `aspectsToSynastry()` and `aspectsToTransits()` all specify which of the secondary chart's planets the primary chart's planets will aspect to, and should only be called after the primary chart and relevant secondary chart have been added to the list.
* `get()` is called at the end of the chain and returns a Laravel collection containing the requested chart data with planets and points and their aspects.

You may validate incoming input against the required birth chart data by using `Chart::validate()`. This accepts an array of inputs and a string describing the type(s) of validation required - either `'chart'` for a standard natal chart, `'solar'` for a solar return chart, `'progressed'` for a progressed chart, `'synastry'` for a synastry chart, or `'optional'` for any of the above optional values (eg. transit date, progression coordinates etc.) It then returns an instance of a standard Laravel `Validator` for you to query.

If you require more information on validation, the valid house systems, input field names, and validation types are all defined in the `ChartValidator` class.

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
    $natalChartData = Chart::create($requestInputs)->addNatalChart()->get();
}

// Same but with current transits, and aspects to the transiting planets
if (Chart::validate($requestInputs)->passes()) {
    $natalChartData = Chart::create($requestInputs)
        ->addNatalChart()
        ->addTransits()
        ->aspectsToTransits()
        ->get();
}

// Add a solar return year
$requestInputs['solar_return_year'] = '2025';

// Validate all - or we could pass "solar" only since "chart" already validated
if (Chart::validate($requestInputs, 'chart', 'solar')->passes()) {
    $solarChartData = Chart::create($requestInputs)->addSolarReturnChart($requestInputs['solar_return_year'])->get();
}

// Add a progression date
$requestInputs['progression_date'] = '2025-07-01';

// Validate just the date
if (Chart::validate($requestInputs, 'progressed')->passes()) {
    $progressedChartData = Chart::create($requestInputs)->addProgressedChart($requestInputs['progression_date'])->get();
}

// Create a synastry chart
$requestInputs += [
    'synastry_date' => '2001-02-15',
    'synastry_time' => '08:30',
    'synastry_latitude' => '38.5616505',
    'synastry_longitude' => '-121.5829968',
];

if (Chart::validate($requestInputs, 'chart', 'synastry')->passes()) {
    $chartData = Chart::create($requestInputs)
        ->addNatalChart()
        ->addSynastryChart($requestInputs['synastry_date'], $requestInputs['synastry_time'], $requestInputs['synastry_latitude'], $requestInputs['synastry_longitude'])
        ->aspectsToSynastry()
        ->get();
}
```
