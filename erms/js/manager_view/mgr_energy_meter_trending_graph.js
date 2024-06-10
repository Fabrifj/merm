(function(m, hc, tc, _, global) {

  var GRAPH_EL = 'energyMeterTrending';
  var GRAPH_TITLE = 'Energy Meter Trending';

  var MgrEnergyMeterTrendingGraphModel = {
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
          type: 'spline',
          name: data.name,
          data: data.values,
          tooltip: {
            pointFormatter: function () {
                return '<span style="color:'+this.color+'">\u25CF</span> '+this.series.name+': <b>'+(global.formatNumber(this.y, 0, '', ' '+data.units.units))+'</b><br/>';
            }

          }
        };
      });
    },
    legendVisible: true,
    toggleLegend: function () {
      this.chart.update({
        legend: {
          enabled: !this.chart.legend.display
        }
      });
      this.legendVisible = !this.legendVisible;
    },
    graph:{
      chart: {
        type: 'spline',
      },
      title: {
          text: ''
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
      legend: {
          shadow: false,
          layout: 'vertical',
          align: 'right',
          verticalAlign: 'middle'
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
      series: []
    }
  };

  var MgrEnergyMeterTrendingGraph = {
    view: function () {
      return m("div", { class: "mgr-main-graph" }, [
        m("div", {  class: "filter-bar" }, [
          m("div", { class: "filter-bar__header" }, "Graph Filter Options"),
          m("label", [
            m("input[type=checkbox]", {
            onclick: MgrEnergyMeterTrendingGraphModel.toggleLegend.bind(MgrEnergyMeterTrendingGraphModel),
            checked: MgrEnergyMeterTrendingGraphModel.legendVisible
          })], "Legend")
        ]),
        m("div", { id: GRAPH_EL, class: "mgr-main-graph__graph" })
      ]);
    }
  };

  global.MgrEnergyMeterTrendingGraph = {
    init: function (opts) {
      this.options = opts;
      this.el = opts.el;
      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), MgrEnergyMeterTrendingGraph);
      return this;
    },
    graph: function () {
      MgrEnergyMeterTrendingGraphModel.setOptions(_.omit(this.options, 'el'))
      // High charts expects the actual id name
     MgrEnergyMeterTrendingGraphModel.chart = hc.chart(GRAPH_EL, MgrEnergyMeterTrendingGraphModel.graph)
     return this;
    }
  };
})(m, Highcharts, tinycolor, _, window);
