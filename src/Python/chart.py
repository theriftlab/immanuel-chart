import re
import sys
import json

from collections import defaultdict
from datetime import datetime
from pytz import timezone, utc
from timezonefinder import TimezoneFinder
from backports.datetime_fromisoformat import MonkeyPatch
MonkeyPatch.patch_fromisoformat()

from flatlib import angle
from flatlib.const import *
from flatlib.aspects import *
from flatlib.chart import Chart
from flatlib.datetime import Datetime
from flatlib.geopos import GeoPos

# get command-line args
args = defaultdict(list)

for k, v in ((k.lstrip('-'), v) for k,v in (a.split('=') for a in sys.argv[1:])):
    args[k] = v

# check we have all the ones we want
required_args = set(('type', 'latitude', 'longitude', 'birth_date', 'birth_time', 'house_system'))

if ('type' in args and args['type'] == 'solar'):
    required_args.add('solar_return_year')

if not required_args <= args.keys():
    sys.exit(json.dumps({'error': 'Missing arguments'}));

# no further validation, as we assume our API will validate actual values for us
chart_type = args['type']
latitude = float(args['latitude'])
longitude = float(args['longitude'])
birth_date = args['birth_date']
birth_time = args['birth_time']
house_system = args['house_system']
solar_return_year = args['solar_return_year'] if 'solar_return_year' in args else 0;

# quick function to get a timezone offset based on location
def get_offset():
    tf = TimezoneFinder()
    target_date = datetime.fromisoformat(birth_date + ' ' + birth_time)
    tz_target = timezone(tf.certain_timezone_at(lng=longitude, lat=latitude))
    date_target = tz_target.localize(target_date)
    date_utc = utc.localize(target_date)
    return int((date_utc - date_target).total_seconds() / 3600)

# function to convert a decimal angle into an array of degrees, minutes and seconds
def format_angle(decimal_angle):
    angle_parts = angle.toString(decimal_angle)[1:].split(':')
    return {
        'degrees': angle_parts[0],
        'minutes': angle_parts[1],
        'seconds': angle_parts[2]
    }

# convert command-line args into data for Chart
timezone_offset=get_offset()
chart_date = Datetime([int(i) for i in birth_date.split('-')], birth_time, timezone_offset)
chart_pos = GeoPos(latitude, longitude)

# produce natal chart, and if we've been given a solar return year, work with a return chart
natal_chart = Chart(chart_date, chart_pos, hsys=house_system, IDs=LIST_OBJECTS)
chart = natal_chart.solarReturn(solar_return_year) if chart_type == 'solar' else natal_chart

# output chart data as JSON
data = {
    'diurnal': chart.isDiurnal(),
    'moonPhase': chart.getMoonPhase(),
    'planets': {},
    'points': {},
    'angles': {},
    'houses': {}
}

aspectNames = {
    0: 'Conjunct',
    30: 'Semisextile',
    36: 'Semiquintile',
    45: 'Semisquare',
    60: 'Sextile',
    72: 'Quintile',
    90: 'Square',
    108: 'Sesquiquintile',
    120: 'Trine',
    135: 'Sesquisquare',
    144: 'Biquintile',
    150: 'Quincunx',
    180: 'Opposite'
}

for obj in chart.objects:
    formatted_obj = {
        'planet': obj.id,
        'sign': obj.sign,
        'chartAngle': obj.lon,
        'signAngle': obj.signlon,
        'formattedChartAngle': format_angle(obj.lon),
        'formattedSignAngle': format_angle(obj.signlon),
        'movement': obj.movement(),
        'aspects': {}
    }

    aspected_objects = chart.objects.getObjectsAspecting(obj, ALL_ASPECTS)

    for asp_obj in aspected_objects:
        aspect = getAspect(obj, asp_obj, ALL_ASPECTS)

        if (aspect.type > -1):
            formatted_obj['aspects'][asp_obj.id.lower()] = {
                'object': asp_obj.id,
                'type': aspectNames[aspect.type],
                'aspect': aspect.type,
                'orb': aspect.orb,
                'movement': aspect.active.movement
            }

    if obj.id in ('North Node', 'South Node', 'Syzygy', 'Pars Fortuna'):
        data['points'][obj.id.lower()] = formatted_obj
    else:
        data['planets'][obj.id.lower()] = formatted_obj

for ang in chart.angles:
    data['angles'][ang.id.lower()] = {
        'angle': ang.id,
        'sign': ang.sign,
        'chartAngle': ang.lon,
        'signAngle': ang.signlon,
        'formattedChartAngle': format_angle(ang.lon),
        'formattedSignAngle': format_angle(ang.signlon)
    }

for hse in chart.houses:
    data['houses'][hse.num()] = {
        'house': hse.num(),
        'sign': hse.sign,
        'chartAngle': hse.lon,
        'signAngle': hse.signlon,
        'formattedChartAngle': format_angle(hse.lon),
        'formattedSignAngle': format_angle(hse.signlon)
    }

print(json.dumps(data))
