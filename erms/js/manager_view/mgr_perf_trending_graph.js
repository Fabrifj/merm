(function(m, hc, tc, global) {

  var GRAPH_EL = 'performanceTrending';
  var GRAPH_TITLE = 'Performance Trending';

  var MgrPerfTrendingGraphModel = {
    chart: {},
    setOptions: function (opts) {
      this.opts = opts;
      this.graph.title.text = GRAPH_TITLE;
      this.graph.subtitle.text = opts.dates[0] + ' to ' + opts.dates[1];
      this.graph.xAxis.categories = opts.categories;

      //this.graph.xAxis.labels.events.click = function (e) {
      //  var text = e.target.textContent;
      //  var shipIndex = opts.ship.indexOf(text);
      //  var link = opts.ship_link[shipIndex];

      //  window.location = link;
      //};

      //this.graph.xAxis.labels.formatter = function () {
      //  var shipIndex = opts.ship.indexOf(this.value);
      //  var style = 'cursor: pointer;';
      //  if(opts.ship_available[shipIndex] === 1) {
      //    style += ' color: red;';
      //  }

      //  return '<span style="' + style + '">' + this.value + '</span>';
      //};

      this.graph.series = this.getSeries(opts);
    },
    getSeries: function (opts, filter) {
      //var placementSpacing = 0.06;
      //var placement1 = 0 - (placementSpacing *6) - (0.11);
      //var placement2 = 0 - (placementSpacing *2.5);
      //var placement3 = 0 + (placementSpacing *1) + (0.11);
      //function pointPlacement (p) {
      //  return function () {
      //    return p += placementSpacing;
      //  };
      //}
      var groupMap = {
        consumptionKWhAvg: {
          color: ["#013A9D", "#347EFE", "#001E51", "#C2D8FF", "#0B64FE"],
          //pointPlacement: pointPlacement(placement1),
          visible: true,
          tooltip: {
            pointFormatter: function () {
              return '<span style="color:'+this.color+'">\u25CF</span> '+this.series.name+': <b>'+(global.formatNumber(this.y, 1, '', ' kWh'))+'</b><br/>'
            }
          },
          dataLabels: {
            enabled: true,
            formatter: function () {
              return global.formatNumber(this.y, 1, '', ' kWh');
            }
          }
        },
        onPeakDemand: {
          color: ["#766D04", "#E3D108", "#F9ED62", "#FCF6B0", "#F8E83A"],
          //pointPlacement: pointPlacement(placement2),
          visible: true,
          tooltip: {
            pointFormatter: function () {
              return '<span style="color:'+this.color+'">\u25CF</span> '+this.series.name+': <b>'+(global.formatNumber(this.y, 1, '', ' kW'))+'</b><br/>'
            }
          },
          dataLabels: {
            enabled: true,
            formatter: function () {
              return global.formatNumber(this.y, 1, '', ' kW');
            }
          }
        },
        costAvgPerDay: {
          color: ["#11CCCC", "#085E5E", "#42F0F0", "#7BF4F4", "#C6FAFA"],
          //pointPlacement: pointPlacement(placement3),
          visible: true,
          tooltip: {
            pointFormatter: function () {
              return '<span style="color:'+this.color+'">\u25CF</span> '+this.series.name+': <b>'+(global.formatMoney(this.y, 0))+'</b><br/>'
            }
          },
          dataLabels: {
            enabled: true,
            formatter: function () {
              return global.formatMoney(this.y, 0);
            }
          }
        }
      };
      var typeMap = {
        baseline: {},
        actual: {},
        goal: {}
      }

      var val = _.chain(opts.data);

      if(filter) {
        val = val.filter(filter)
      }

      return val.map(function (dataPoint, index, list) {
          var series = {
            name: dataPoint.name,
            //pointPadding: 0.25,
            //groupPadding: 0.43,
            //pointPlacement: groupMap[dataPoint.group].pointPlacement(),
            data: dataPoint.values,
            id: dataPoint.group+dataPoint.type+index
          };

          if(_.isEmpty(this.chart)) {
            series.visible = dataPoint.visible === false ? false : true;
          }

          if(typeMap[dataPoint.type].dataLabels) {
            series.dataLabels = groupMap[dataPoint.group].dataLabels;
          }

          series.color = groupMap[dataPoint.group].color.pop();
          if(dataPoint.type === "actual") {
            series.type = 'spline';
          //  var color = groupMap[dataPoint.group].color.pop();
          //  series.borderColor = color;
          //  series.borderWidth = 4;
          //  series.color = "#fff";
          //  series.dataLabels.backgroundColor = "#fff";
          //  series.dataLabels.color = color;
          //  series.dataLabels.style = { fontSize: '16px' };
          //  series.dataLabels.borderColor = color;
          //  series.dataLabels.borderWidth = 1;
          //  series.dataLabels.borderRadius = 25;
          //  series.dataLabels.padding = 8;
          //  series.dataLabels.y = -8;
          } else {
            series.type = 'column';
          }

          if(groupMap[dataPoint.group].tooltip) {
            series.tooltip = groupMap[dataPoint.group].tooltip;
          }


          if(dataPoint.yaxis) {
            series.yAxis = dataPoint.yaxis;
          }

          return series;
      }.bind(this)).value();
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
    visibleMap: {
      "baseline": true,
      "costAvgPerDay": true,
      "onPeakDemand": false,
      "consumptionKWhAvg": false,
      "goal": false
    },
    visMapRegEx: function (dontFind) {
        return new RegExp(_.reduce(this.visibleMap, function (visMapRegEx, value, key) {
          if(key !== dontFind) {
            if(!visMapRegEx) {
              return '(' + key + ')';
            }
            return '(' + visMapRegEx.slice(1, visMapRegEx.length-1) + '|' + key + ')';
          }
          return visMapRegEx;
        },''), 'g');
    },
    toggler: function (group) {
      var theOthersRegEx = this.visMapRegEx(group);
      var toggleGroupSeries = function () {
        var series = _.map(this.getSeries(this.opts), function (s) {
          var rgx = new RegExp(group);
          if(rgx.test(s.id)) {
            var match = s.id.match(theOthersRegEx);
            var othersMatchFalse = _.reduce(match, function (othersMatchFalse, value) {
              return this.visibleMap[value] === false;
            }, false, this);
            s.visible = (!othersMatchFalse) ? !this.visibleMap[group] : false;
          }
          return s;
        }, this);
        this.chart.update({
          series: series,
        });
        this.visibleMap[group] = !this.visibleMap[group]
      }.bind(this);

      return toggleGroupSeries;
    },
    legacy: global.legacy,
    graph:{
      chart: {
          plotBorderWidth: 1
      },
      title: {
          text: ''
      },
      subtitle: {
        text: ''
      },
      xAxis: {
          categories: [],
          labels: {
            events: {}
          }
      },
      yAxis: [{
          id: 'consumption',
          min: 0,
          title: {
              text: 'Consumption (kWh/day)'
          },
          alternateGridColor: '#f4f4f4',
      }, {
          id: 'demand',
          title: {
              text: 'Demand (kW) / Cost ($/day)'
          },
          alternateGridColor: '#f4f4f4',
          opposite: true
      }],
      legend: {
          enabled: false,
          shadow: false,
          layout: 'vertical',
          align: 'right',
          verticalAlign: 'middle'
      },
      tooltip: {
          shared: false
      },
      plotOptions: {
          column: {
              grouping: false,
              shadow: false,
              borderWidth: 1
          },
          series: {
            events: {
              legendItemClick: function () {
              }
            }
          }
      },
      series: []
    }
  };

  var MgrPerfTrendingGraph = {
    view: function () {
      return m("div", { class: "mgr-main-graph" }, [
        m("div", {  class: "filter-bar" }, [
          m("div", { class: "filter-bar__header" }, "Graph Filter Options"),
          m("label", [
            m("input[type=checkbox]", {
            onclick: MgrPerfTrendingGraphModel.toggleLegend.bind(MgrPerfTrendingGraphModel),
            checked: MgrPerfTrendingGraphModel.legendVisible
          })], "Legend"),
          m("label", [
            m("input[type=checkbox]", {
            onclick: MgrPerfTrendingGraphModel.toggler("consumptionKWhAvg").bind(MgrPerfTrendingGraphModel),
            checked: MgrPerfTrendingGraphModel.visibleMap["consumptionKWhAvg"]
          })], "kWh Consumption"),
          m("label", [
            m("input[type=checkbox]", {
            onclick: MgrPerfTrendingGraphModel.toggler("onPeakDemand").bind(MgrPerfTrendingGraphModel),
            checked: MgrPerfTrendingGraphModel.visibleMap["onPeakDemand"]
          })], "On Peak Demand"),
          m("label", [
            m("input[type=checkbox]", {
            onclick: MgrPerfTrendingGraphModel.toggler("costAvgPerDay").bind(MgrPerfTrendingGraphModel),
            checked: MgrPerfTrendingGraphModel.visibleMap["costAvgPerDay"]
          })], "Avg Cost Per Day"),
          m("label", [
            m("input[type=checkbox]", {
            onclick: MgrPerfTrendingGraphModel.toggler("baseline").bind(MgrPerfTrendingGraphModel),
            checked: MgrPerfTrendingGraphModel.visibleMap["baseline"]
          })], "Baseline"),
          m("label", [
            m("input[type=checkbox]", {
            onclick: MgrPerfTrendingGraphModel.toggler("goal").bind(MgrPerfTrendingGraphModel),
            checked: MgrPerfTrendingGraphModel.visibleMap["goal"]
          })], "Goals")
        ]),
        m("div", { id: GRAPH_EL, class: "mgr-main-graph__graph" })
      ]);
    }
  };

  global.MgrPerfTrendingGraph = {
    init: function (opts) {
      this.options = opts;
      this.el = opts.el;
      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), MgrPerfTrendingGraph);
      return this;
    },
    graph: function () {
      MgrPerfTrendingGraphModel.setOptions(_.omit(this.options, 'el'))
      // High charts expects the actual id name
     MgrPerfTrendingGraphModel.chart = hc.chart(GRAPH_EL, MgrPerfTrendingGraphModel.graph)
     return this;
    }
  };
})(m, Highcharts, tinycolor, window);
