(function($, _, m, moment, global) {
  var POWER_AND_COST_ANALYSIS = 'mod1';
  var ENERGY_METER_TRENDING = 'mod3';
  var PERFORMANCE_TRENDING = 'mod8';
  var SUBTITLE_DATE_FORMAT = 'MMMM Do YYYY';

  global.download = function download (strData, strFileName, strMimeType) {
    var D = document,
    a = D.createElement('a');
    strMimeType= strMimeType || 'application/octet-stream';

    if (navigator.msSaveBlob) { // IE10+
      return navigator.msSaveBlob(new Blob([strData], {type: strMimeType}), strFileName);
    } /* end if(navigator.msSaveBlob) */

    if ('download' in a) { //html5 A[download]
      if(global.URL){
        a.href= global.URL.createObjectURL(new Blob([strData]));
      } else {
        a.href = 'data:' + strMimeType + ',' + encodeURIComponent(strData);
      }
      a.setAttribute('download', strFileName);
      a.innerHTML = 'downloading...';
      D.body.appendChild(a);
      setTimeout(function() {
        a.click();
        D.body.removeChild(a);
        if(global.URL) {
          setTimeout(function(){ global.URL.revokeObjectURL(a.href);}, 250 );
        }
      }, 66);
      return true;
    } /* end if('download' in a) */


    //do iframe dataURL download (old ch+FF):
    var f = D.createElement('iframe');
    D.body.appendChild(f);
    f.src = 'data:' +  strMimeType   + ',' + encodeURIComponent(strData);

    setTimeout(function() {
      D.body.removeChild(f);
    }, 333);
    return true;
  };

  global.write_to_excel = function write_to_excel (tableid) {
    if (navigator.appCodeName === 'Mozilla') {
      //alert( 'Browser1 ' + navigator.appCodeName );
      global.open('data:application/vnd.ms-excel, ' + '<table>'+encodeURIComponent($('#group_table').html()) + '</table>'  );
    } else {
      //alert( 'Browser2 ' + navigator.appCodeName );
      if (!tableid.nodeType) {
        tableid = document.getElementById(tableid);
      }

      var shipName =  '<?php echo $Meter_Name; ?>';
      var noSpacesName = shipName.replace(' ', '');
      var dt = new Date();
      var day = dt.getDate();
      var month = dt.getMonth() + 1;
      var year = dt.getFullYear();
      var postfix = month + '-' + day + '-' + year;
      var filename =  noSpacesName + postfix + '.xls';
      download(group_table.outerHTML, filename, 'application/vnd.ms-excel');
    }
  }

  global.formatNumber = function formatNumber (num, decimals, pref, suff) {
    decimals = decimals || 0;
    pref = pref || '';
    suff = suff || '';
    var formatted = parseFloat(Math.round(num * 100) / 100).toFixed(decimals);
    var parts = formatted.toString().split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    var commaFormatted = parts.join('.');
    return pref + (commaFormatted) + suff;
  };

  global.formatMoney = function formatMoney (val, decimals, suff) {
    decimals = _.isUndefined(decimals) ? 2 : decimals;
    return formatNumber(val, decimals, '$', suff);
  };

  global.momentValueOfOffset = function momentValueOfOffset (momentTzObj, timezone) {
          var cTzObj = momentTzObj.clone();
          var absOffset = moment.tz.zone(timezone).offset(cTzObj);

          if(cTzObj._offset > 0) {
            cTzObj.add(absOffset, 'm');
          } else if(cTzObj._offset < 0){
            cTzObj.subtract(absOffset, 'm');
          }

          return cTzObj.valueOf();
  }

  function buildEnergyMeterTrendingTableGroupModel () {
    var headers = [];
    var metricAvg = [];
    var units;

    _.each(global.ships_data, function (value, key, list) {
      var header = {
        title: value.title,
        path: value.home_path
      }
      var energy_meter_trending = value.energy_meter_trending;
      var avg = energy_meter_trending && energy_meter_trending.avg;
      units = (energy_meter_trending && energy_meter_trending.units);
      header.hasData = !!avg;
      headers.push(header);
      var formattedAvg = avg ? formatNumber(avg, 0, '', ' ' + units.units) : 'N/A';
      metricAvg.push(formattedAvg);
    });
    var energyMeterTrendingTableGroupOpts = {
      metrics: {},
      el: '#metricsTable',
      headers: headers,
      title: 'Energy Meter Metric Average'
    };
    energyMeterTrendingTableGroupOpts.metrics[units.name] = metricAvg;
    return energyMeterTrendingTableGroupOpts;
  }

  function buildConsumptionTableGroupModel () {
    var kWhPerDay = [];
    var onPeakDemand = [];
    var costPerDay = [];
    var layDays = [];
    var headers = [];
    var hasAllLayDays = [];

    _.each(global.ships_data, function (value, key, list) {
      headers.push({
        title: value.title,
        hasData: value.has_data,
        path: value.home_path
      });
      kWhPerDay.push(formatNumber(value.kWh_day, 0, '', ' kWh'));
      onPeakDemand.push(formatNumber(value.Peak_Demand, 0, '', ' kW'));
      var gtLayDay;
      if(!value.has_all_lay_days) {
        gtLayDay = formatMoney(value.Grand_Total_Lay_Day, 0, '*');
      } else {
        gtLayDay = formatMoney(value.Grand_Total_Lay_Day, 0);
      }
      costPerDay.push(gtLayDay);
      layDays.push(formatNumber(value.Lay_Days, 2, '', ' days'));
      hasAllLayDays.push(value.has_all_lay_days);
    });
    var consumptionTableGroupOpts = {
      hasAllLayDays: hasAllLayDays,
      metrics: {},
      el: '#metricsTable',
      headers: headers,
      title: 'Consumption and Usage Summary'
    };
    consumptionTableGroupOpts.metrics['Consumption(kWh) Avg per Day'] = kWhPerDay;
    if(global.isAnnualReport) {
        consumptionTableGroupOpts.metrics['On-Peak Demand (Monthly Avg)'] = onPeakDemand;
    } else {
        consumptionTableGroupOpts.metrics['On-Peak Demand'] = onPeakDemand;
    }
    consumptionTableGroupOpts.metrics['Cost per Day'] = costPerDay;
    consumptionTableGroupOpts.metrics['Lay Days'] = layDays;
    return consumptionTableGroupOpts;
  }

  var navBarOpts = {
    el: '#navBar',
    mods: global.user_data.mgrMods,
    currentMod: global.module
  };
  var navBar = global.NavBar.init(navBarOpts).mount();
  var permittedShipClasses = _.map(global.user_data.permittedShipClasses, function (group, cls) {
    group.name = group.name + ' Class';
    return group;
  })
  var navSubBarOpts = {
    el: '#navSubBar',
    permittedShipClasses: permittedShipClasses,
    currentClass: global.shipClass,
  };
  var navSubBar = global.NavSubBar.init(navSubBarOpts).mount();
  var breadcrumbOpts = {
    el: '#breadcrumbs',
    breadcrumbs: global.user_data.breadcrumbs
  }
  var breadcrumbs = global.Breadcrumbs.init(breadcrumbOpts).mount();
  switch(global.module) {
    case POWER_AND_COST_ANALYSIS:
      var mgrMainGraph = global.MgrMainGraph.init(_.extend({el: '#mgrMainGraph'}, global.graph)).mount();
      mgrMainGraph.graph();
      global.DataExportForm.init({el: '#rawDataExport'}).mount();
      global.ConsumptionTableGroup.init(buildConsumptionTableGroupModel()).mount();
      break;
    case PERFORMANCE_TRENDING:
      var mgrPerfTrendingGraph = global.MgrPerfTrendingGraph.init(_.extend({el: '#mgrMainGraph'}, global.graph)).mount();
      mgrPerfTrendingGraph.graph();
      //global.DataExportForm.init({el: '#rawDataExport'}).mount();
      //global.ConsumptionTableGroup.init(buildConsumptionTableGroupModel()).mount();
      break;
    case ENERGY_METER_TRENDING:
      var sDate = moment.tz(global.graph.date_start, global.graph.timezone);
      var eDate = moment.tz(global.graph.date_end, global.graph.timezone);
      var formattedStartDate = sDate.format(SUBTITLE_DATE_FORMAT);
      var formattedEndDate = eDate.format(SUBTITLE_DATE_FORMAT);

      var energyMeterGraphOpts = {
        el: '#mgrMainGraph',
        subtitle: formattedStartDate + ' to ' + formattedEndDate,
        pointInterval: global.graph.log_interval,
        pointStart: momentValueOfOffset(sDate, global.graph.timezone),
        data: global.graph.data,
        units: global.graph.units,
        timezone: global.graph.timezone
      };
      var mgrEnergyMeterTrendingGraph = global.MgrEnergyMeterTrendingGraph.init(energyMeterGraphOpts).mount();
      mgrEnergyMeterTrendingGraph.graph();
      global.EnergyMeterTrendingTableGroup.init(buildEnergyMeterTrendingTableGroupModel()).mount();

      break;
  }
})($, _, m, moment, window);
