(function(m, hc, tc, _, moment, global) {
  var GRAPH_EL = 'powerCostAnalysis';
  var GRAPH_TITLE = 'Power & Demand Analysis';
  var GRAPH_YAXIS_TITLE = 'Killowatts (kW)';
  var SERIES_Y1 = 'Real Power (kW)';
  var SERIES_Y2 = '30 Min Demand (kW)';
  //var SERIES_Y2_LINE_COLOR = 'red';

  var PowerCostGraphModel = {
    chart: {},
    setOptions: function (opts) {
      this.opts = opts;
      this.graph.title.text = GRAPH_TITLE;
      this.graph.subtitle.text = opts.subtitle;
      this.graph.series = this.getSeries(opts);
      this.graph.xAxis.plotBands = this.getPlotBands(opts);
      this.graph.plotOptions.series.pointInterval = opts.pointInterval;
      this.graph.plotOptions.series.pointStart = opts.pointStart;
    },
    getSeries: function (opts) {
      return [{
        name: SERIES_Y1,
        data: opts.data.y1,
        tooltip: {
          pointFormatter: function () {
              return '<span style="color:'+this.color+'">\u25CF</span> '+this.series.name+': <b>'+(global.formatNumber(this.y, 0, '', ' kW'))+'</b><br/>'
          }
        }
      }, {
        name: SERIES_Y2,
        data: opts.data.y2,
        tooltip: {
          pointFormatter: function () {
              return '<span style="color:'+this.color+'">\u25CF</span> '+this.series.name+': <b>'+(global.formatNumber(this.y, 0, '', ' kW'))+'</b><br/>'
          }
        }
      }];
    },
    getPlotBands: function (opts) {
      return _.map(opts.plotBands, function (pb) {
        return {
          color: 'rgba(255, 194, 194, .5)',
          from: global.momentValueOfOffset(moment.tz(pb.from, opts.timezone), opts.timezone),
          to: global.momentValueOfOffset(moment.tz(pb.to, opts.timezone), opts.timezone),
          zIndex: 3
        };
      })
    },
    legendVisible: false,
    toggleLegend: function () {
      this.chart.update({
        legend: {
          enabled: !this.chart.legend.display
        }
      });
      this.legendVisible = !this.legendVisible;
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
        plotBands: []
      },
      yAxis: {
        title: {
          text: GRAPH_YAXIS_TITLE
        }
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

  var PowerCostGraph = {
    view: function () {
      return m("div", { class: "power-cost-graph" }, [
        m("div", { id: GRAPH_EL, class: "main-graph__graph" })
      ]);
    }
  };

  global.PowerCostGraph = {
    init: function (opts) {
      this.options = opts;
      this.el = opts.el;

      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), PowerCostGraph);
      return this;
    },
    graph: function () {
      PowerCostGraphModel.setOptions(_.omit(this.options, 'el'));
      PowerCostGraphModel.chart = hc.chart(GRAPH_EL, PowerCostGraphModel.graph);
      return this;
    }
  };
})(m, Highcharts, tinycolor, _, moment, window);
