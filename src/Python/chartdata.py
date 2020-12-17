from riftlib import const, angle
from riftlib.aspects import getAspect
from riftlib.chart import Chart


class ChartData:

    # initialise our chart data
    def __init__(self, primary_chart, secondary_chart=None, aspects='primary'):
        self.aspect_names = {
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

        self.primary_chart = primary_chart
        self.secondary_chart = secondary_chart
        self.aspects = aspects
        self.set_data()

    # populate the data we're going to return
    def set_data(self):
        self.data = {
            'chartDate': self.format_chart_date(),
            'diurnal': self.primary_chart.isDiurnal(),
            'nocturnal': not self.primary_chart.isDiurnal(),
            'moonPhase': self.primary_chart.getMoonPhase(),
            'planets': {},
            'points': {},
            'angles': {},
            'houses': {}
        }

        self.populate_object_data()
        self.populate_angle_data()
        self.populate_house_data()

    # planets & points
    def populate_object_data(self):
        if self.aspects == 'secondary':
            objects = self.secondary_chart.objects
        else:
            objects = self.primary_chart.objects

        for obj in objects:
            formatted_obj = {
                'planet': obj.id,
                'sign': obj.sign,
                'chartAngle': obj.lon,
                'signAngle': obj.signlon,
                'formattedChartAngle': self.format_angle(obj.lon),
                'formattedSignAngle': self.format_angle(obj.signlon),
                'movement': obj.movement(),
                'aspects': {}
            }

            if self.aspects == 'primary':
                aspected_objects = self.primary_chart.objects.getObjectsAspecting(obj, const.ALL_ASPECTS)
            else:
                aspected_objects = self.secondary_chart.objects.getObjectsAspecting(obj, const.ALL_ASPECTS)

            for asp_obj in aspected_objects:
                aspect = getAspect(obj, asp_obj, const.ALL_ASPECTS)

                if aspect.type > -1:
                    formatted_obj['aspects'][asp_obj.id.lower()] = {
                        'object': asp_obj.id,
                        'type': self.aspect_names[aspect.type],
                        'aspect': aspect.type,
                        'orb': aspect.orb,
                        'movement': aspect.active.movement
                    }

            if obj.id in ('North Node', 'South Node', 'Syzygy', 'Pars Fortuna'):
                self.data['points'][obj.id.lower()] = formatted_obj
            else:
                self.data['planets'][obj.id.lower()] = formatted_obj

    # the four angles
    def populate_angle_data(self):
        for ang in self.primary_chart.angles:
            self.data['angles'][ang.id.lower()] = {
                'angle': ang.id,
                'sign': ang.sign,
                'chartAngle': ang.lon,
                'signAngle': ang.signlon,
                'formattedChartAngle': self.format_angle(ang.lon),
                'formattedSignAngle': self.format_angle(ang.signlon)
            }

    # house cusps
    def populate_house_data(self):
        for hse in self.primary_chart.houses:
            self.data['houses'][hse.num()] = {
                'house': hse.num(),
                'sign': hse.sign,
                'chartAngle': hse.lon,
                'signAngle': hse.signlon,
                'formattedChartAngle': self.format_angle(hse.lon),
                'formattedSignAngle': self.format_angle(hse.signlon)
            }

    # format the date into a SOAP-friendly text format
    def format_chart_date(self):
        offset = self.primary_chart.date.utcoffset.toList()
        formatted_chart_date = '-'.join(str(i).zfill(2) for i in self.primary_chart.date.date.toList()[1:])
        formatted_chart_date += 'T'
        formatted_chart_date += ':'.join(str(i).zfill(2) for i in self.primary_chart.date.time.toList()[1:])
        formatted_chart_date += str(offset[0]) + str(offset[1]).zfill(2) + ':' + str(offset[2]).zfill(2)
        return formatted_chart_date

    # convert a decimal angle into an array of degrees, minutes and seconds
    def format_angle(self, decimal_angle):
        angle_parts = angle.toString(decimal_angle)[1:].split(':')

        return {
            'degrees': angle_parts[0],
            'minutes': angle_parts[1],
            'seconds': angle_parts[2]
        }