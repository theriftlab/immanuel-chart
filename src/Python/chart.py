import re, sys, json, string

from collections import defaultdict
from dateutil.relativedelta import relativedelta
from datetime import datetime, date
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

# calculate a progression chart date
def progression_chart_date():
    progression_date_dt = tz.localize(datetime.fromisoformat(progression_date));
    last_birthday_dt = birth_dt_local.replace(year=progression_date_dt.year)

    # the progression year is counted from birthday to birthday
    # this calculates how far along in the year the progression date is
    if (last_birthday_dt > progression_date_dt):
        next_birthday_dt = last_birthday_dt
        last_birthday_dt -= relativedelta(years=1)
    else:
        next_birthday_dt = last_birthday_dt + relativedelta(years=1)

    year_length = (next_birthday_dt - last_birthday_dt).days * 24
    days_passed = (progression_date_dt - last_birthday_dt).days * 24
    days_passed += relativedelta(progression_date_dt, last_birthday_dt).hours
    days_passed_ratio = days_passed / year_length

    # calculate progression chart date / time
    days = relativedelta(progression_date_dt, birth_dt_local).years
    hours = days_passed_ratio * 24
    return (birth_dt + relativedelta(days=days, hours=hours))

# convert a decimal angle into an array of degrees, minutes and seconds
def format_angle(decimal_angle):
    angle_parts = angle.toString(decimal_angle)[1:].split(':')
    return {
        'degrees': angle_parts[0],
        'minutes': angle_parts[1],
        'seconds': angle_parts[2]
    }

# get command-line args & check we have all the ones we want
args = defaultdict(list)

for k, v in ((k.lstrip('-'), v) for k,v in (a.split('=') for a in sys.argv[1:])):
    args[k] = v

required_args = set(('type', 'latitude', 'longitude', 'birth_date', 'birth_time', 'house_system'))

if ('type' in args):
    # year is required for solar returns
    if (args['type'] == 'solar'):
        required_args.add('solar_return_year')
    # date is required for progressed chart
    elif (args['type'] == 'progressed'):
        required_args.add('progression_date')

# ensure they all exist
if not required_args <= args.keys():
    sys.exit(json.dumps({'error': 'Missing arguments'}));

# no further validation, as we assume our API will validate actual values for us
chart_type = args['type']
latitude = float(args['latitude'])
longitude = float(args['longitude'])
birth_date = args['birth_date']
birth_time = args['birth_time']
house_system = string.capwords(args['house_system'])
solar_return_year = args['solar_return_year'] if 'solar_return_year' in args else 0;
progression_date = args['progression_date'] if 'progression_date' in args else 0;

# set up date localisation
tf = TimezoneFinder()
tz = timezone(tf.certain_timezone_at(lng=longitude, lat=latitude))
# calculate timezone offset
birth_dt = datetime.fromisoformat(birth_date + ' ' + birth_time)
birth_dt_local = tz.localize(birth_dt)
birth_dt_utc = utc.localize(birth_dt)
timezone_offset = int((birth_dt_utc - birth_dt_local).total_seconds() / 3600)
# get a location
chart_pos = GeoPos(latitude, longitude)

# generate the chart based on --type arg
if (chart_type == 'progressed'):
    # use progressed date for main chart
    pcd = progression_chart_date()
    chart_date = Datetime([pcd.year, pcd.month, pcd.day], ['+', pcd.hour, pcd.minute, pcd.second], timezone_offset)
else:
    # use passed birth date & time for main chart
    chart_date = Datetime([int(i) for i in birth_date.split('-')], birth_time, timezone_offset)

# produce the main chart
main_chart = Chart(chart_date, chart_pos, hsys=house_system, IDs=LIST_OBJECTS)
# if we've been given a solar return year, Flatlib needs the natal chart to produce a solar return
chart = main_chart.solarReturn(solar_return_year) if chart_type == 'solar' else main_chart

# format chart date into PHP/SOAP-friendly format
offset = chart.date.utcoffset.toList()
soap_chart_date = '-'.join(str(i).zfill(2) for i in chart.date.date.toList()[1:])
soap_chart_date += 'T'
soap_chart_date += ':'.join(str(i).zfill(2) for i in chart.date.time.toList()[1:])
soap_chart_date += str(offset[0]) + str(offset[1]).zfill(2) + ':' + str(offset[2]).zfill(2)

# collate data into JSON object
data = {
    'chartDate': soap_chart_date,
    'diurnal': chart.isDiurnal(),
    'nocturnal': not chart.isDiurnal(),
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
        'movement': obj.movement() if (obj.movement() != 'Retrogade') else 'Retrograde',
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
