(function(m, hc, tc, _, moment, global) {
  var GRAPH_EL = 'energyMeter';
  var GRAPH_TITLE = 'Energy Meter Data';

  var EnergyMeterGraphModel = {
    chart: {},
    setOptions: function (opts) {
      this.opts = opts;
      this.graph.title.text = GRAPH_TITLE;
      this.graph.subtitle.text = opts.subtitle;
      this.graph.series = this.getSeries(opts);
      this.graph.yAxis = this.getyAxis(opts);
      this.graph.plotOptions.series.pointInterval = opts.pointInterval;
      this.graph.plotOptions.series.pointStart = opts.pointStart;
    },
    getyAxis: function (opts) {
      return _.map(opts.units, function (units, y) {
        var yaxis = {
          id: units.field,
          title: {
            text: units.name
          },
          alternateGridColor: '#f4f4f4',
          alignTicks: false,
          allowDecimals: true
        };
        if(y > 0) {
          yaxis.opposite = true;
        }

        return yaxis;
      });
    },
    getSeries: function (opts) {
      return _.map(opts.data, function (data, y) {
        return {
          yAxis: y,
          name: opts.units[y].name,
          data: data,
          tooltip: {
            pointFormatter: function () {
                return '<span style="color:'+this.color+'">\u25CF</span> '+this.series.name+': <b>'+(global.formatNumber(this.y, 0, '', ' '+opts.units[y].units))+'</b><br/>';
            }

          }
        };
      });
    },
    graph: {
      chart: {
        type: 'spline',
      },
      title: {
        text: GRAPH_TITLE
      },
      subtitle: {
        text: ''
      },
      xAxis: {
        type: 'datetime',
        labels: {
          overflow: 'justify'
        },
        title: {
          enabled: true,
          text: 'Time (' + global.graph.timezone + ')',
          style: {
            fontSize: '14px'
          }
        },
      },
      plotOptions: {
        series: {
          lineWidth: 1.5,
          marker: {
            enabled: false
          },
          pointInterval: null,
          pointStart: null
        }
      },
      legend: {
        align: 'left',
        verticalAlign: 'top',
        floating: true,
        layout: 'vertical',
        borderWidth: 1
      }
    }
  };

  var EnergyMeterGraph = {
    view: function () {
      return m('div', { class: 'energy-meter-graph' }, [
        m('div', { id: GRAPH_EL, class: 'main-graph__graph' })
      ]);
    }
  };

  global.EnergyMeterGraph = {
    init: function (opts) {
      this.options = opts;
      this.el = opts.el;

      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), EnergyMeterGraph);
      return this;
    },
    graph: function () {
      EnergyMeterGraphModel.setOptions(_.omit(this.options, 'el'));
      EnergyMeterGraphModel.chart = hc.chart(GRAPH_EL, EnergyMeterGraphModel.graph);
      return this;
    }
  };
})(m, Highcharts, tinycolor, _, moment, window);
