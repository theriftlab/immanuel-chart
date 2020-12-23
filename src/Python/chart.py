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

if 'secondary_type' in args and args['secondary_type'] == 'synastry':
    required_args.update(['synastry_date', 'synastry_time', 'synastry_latitude', 'synastry_longitude'])

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
secondary_chart_type = args['secondary_type'] if 'secondary_type' in args else None
with_transits = args['with_transits'] if 'with_transits' in args else None

aspects = args['aspects'] if 'aspects' in args else 'primary'

solar_return_year = args['solar_return_year'] if 'solar_return_year' in args else None
solar_return_latitude = float(args['solar_return_latitude']) if 'solar_return_latitude' in args else None
solar_return_longitude = float(args['solar_return_longitude']) if 'solar_return_longitude' in args else None

progression_date = args['progression_date'] if 'progression_date' in args else None
progression_latitude = float(args['progression_latitude']) if 'progression_latitude' in args else None
progression_longitude = float(args['progression_longitude']) if 'progression_longitude' in args else None

transit_latitude = float(args['transit_latitude']) if 'transit_latitude' in args else None
transit_longitude = float(args['transit_longitude']) if 'transit_longitude' in args else None
transit_date = args['transit_date'] if 'transit_date' in args else None
transit_time = args['transit_time'] if 'transit_time' in args else None

force_primary_chart_key = args['force_primary_chart_key'] if 'force_primary_chart_key' in args else None

if secondary_chart_type == 'synastry':
    synastry_date = args['synastry_date']
    synastry_time = args['synastry_time']
    synastry_latitude = float(args['synastry_latitude'])
    synastry_longitude = float(args['synastry_longitude'])

# generate the main chart
pos = GeoPos(latitude, longitude)
datetime = Datetime(date=birth_date, time=birth_time, pos=pos)
chart = Chart(datetime, pos, hsys=house_system, IDs=const.LIST_OBJECTS)

# generate any extra charts
if chart_type == 'solar' or secondary_chart_type == 'solar':
    solar_return_pos = GeoPos(solar_return_latitude, solar_return_longitude) if solar_return_latitude is not None and solar_return_longitude is not None else pos
    solar_return_chart = chart.solarReturn(solar_return_year, solar_return_pos)

if chart_type == 'progressed' or secondary_chart_type == 'progressed':
    progression_pos = GeoPos(progression_latitude, progression_longitude) if progression_latitude is not None and progression_longitude is not None else pos
    progressed_chart = chart.progressedChart(progression_date, progression_pos)

if secondary_chart_type == 'synastry':
    synastry_pos = GeoPos(synastry_latitude, synastry_longitude)
    synastry_datetime = Datetime(date=synastry_date, time=synastry_time, pos=synastry_pos)
    synastry_chart = Chart(synastry_datetime, synastry_pos, hsys=house_system, IDs=const.LIST_OBJECTS)

if with_transits == 'true':
    transit_pos = GeoPos(transit_latitude, transit_longitude) if transit_latitude is not None and transit_longitude is not None else pos
    transit_datetime = Datetime(date=transit_date, time=transit_time, pos=transit_pos) if transit_date is not None and transit_time is not None else 0
    transit_chart = chart.transits(transit_datetime, transit_pos)

# work out which charts to return
if chart_type == 'natal':
    primary_chart = chart
elif chart_type == 'solar':
    primary_chart = solar_return_chart
elif chart_type == 'progressed':
    primary_chart = progressed_chart

if secondary_chart_type == 'natal':
    secondary_chart = chart
elif secondary_chart_type == 'solar':
    secondary_chart = solar_return_chart
elif secondary_chart_type == 'progressed':
    secondary_chart = progressed_chart
elif secondary_chart_type == 'synastry':
    secondary_chart = synastry_chart

# calculate requested aspects
if aspects == 'primary':
    chart_data = ChartData(primary_chart)
elif aspects == 'secondary':
    chart_data = ChartData(primary_chart, secondary_chart)
elif aspects == 'transits':
    chart_data = ChartData(primary_chart, transit_chart)

# return requested chart data
return_data = {
    'primary': chart_data.data
}

if secondary_chart_type in ['solar', 'progressed', 'synastry']:
    return_data['secondary'] = ChartData(secondary_chart).data

if with_transits:
    return_data['transits'] = ChartData(transit_chart).data

if force_primary_chart_key != 'true' and secondary_chart_type is None and with_transits != 'true':
    return_data = return_data['primary']

print(json.dumps(return_data))