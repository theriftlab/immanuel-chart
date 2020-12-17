import sys, json, string

from chartdata import ChartData
from collections import defaultdict
from datetime import datetime

from riftlib import const
from riftlib.chart import Chart
from riftlib.datetime import Datetime
from riftlib.geopos import GeoPos


# get command-line args & check we have all the ones we want
args = defaultdict(list)

for k, v in ((k.lstrip('-'), v) for k,v in (a.split('=') for a in sys.argv[1:])):
    args[k] = v

required_args = set(('type', 'latitude', 'longitude', 'birth_date', 'birth_time', 'house_system'))

if 'type' in args:
    # year is required for solar returns
    if args['type'] == 'solar':
        required_args.add('solar_return_year')
    # date is required for progressed chart
    elif args['type'] == 'progressed':
        required_args.add('progression_date')

if 'return_chart' in args and args['return_chart'] == 'both':
    required_args.add('mixed_chart_aspects')

# ensure they all exist
if not required_args <= args.keys():
    sys.exit(json.dumps({'error': 'Missing arguments'}))

# store required args
chart_type = args['type']
latitude = float(args['latitude'])
longitude = float(args['longitude'])
birth_date = args['birth_date']
birth_time = args['birth_time']
house_system = string.capwords(args['house_system'])

# store optional args
return_chart = args['return_chart'] if 'return_chart' in args else 'primary'
mixed_chart_aspects = args['mixed_chart_aspects'] if 'mixed_chart_aspects' in args else 'both'
solar_return_year = args['solar_return_year'] if 'solar_return_year' in args else None
progression_date = args['progression_date'] if 'progression_date' in args else None
transit_date = args['transit_date'] if 'transit_date' in args else None
transit_time = args['transit_time'] if 'transit_time' in args else None
secondary_latitude = args['secondary_latitude'] if 'secondary_latitude' in args else None
secondary_longitude = args['secondary_longitude'] if 'secondary_longitude' in args else None

# generate the chart
primary_pos = GeoPos(latitude, longitude)
primary_date = Datetime(date=birth_date, time=birth_time, pos=primary_pos)
primary_chart = Chart(primary_date, primary_pos, hsys=house_system, IDs=const.LIST_OBJECTS)

# see if we need a secondary chart
secondary_pos = GeoPos(secondary_latitude, secondary_longitude) if secondary_latitude is not None and secondary_longitude is not None else primary_pos

if chart_type == 'solar':
    secondary_chart = primary_chart.solarReturn(solar_return_year, secondary_pos)
elif chart_type == 'progressed':
    secondary_chart = primary_chart.progressedChart(progression_date, secondary_pos)
elif chart_type == 'transits':
    transit_datetime = Datetime(date=transit_date, time=transit_time, pos=secondary_pos) if transit_date is not None and transit_time is not None else 0
    secondary_chart = primary_chart.transits(transit_datetime, secondary_pos)
else:
    secondary_chart = None

# now choose what to return
if return_chart == 'primary':
    chart_data = ChartData(primary_chart)
    print(json.dumps(chart_data.data))
elif secondary_chart is not None:
    if return_chart == 'secondary':
        chart_data = ChartData(secondary_chart)
        print(json.dumps(chart_data.data))
    elif return_chart == 'both':
        chart_data = ChartData(primary_chart, secondary_chart, mixed_chart_aspects)
        secondary_chart_data = ChartData(secondary_chart, primary_chart, mixed_chart_aspects)
        print(json.dumps({
            'primary': chart_data.data,
            'secondary': secondary_chart_data.data,
            'aspects': mixed_chart_aspects,
        }))