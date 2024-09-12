(function($, _, m, moment, global) {
  var OVERVIEW = 'mod0';
  var POWER_AND_COST_ANALYSIS = 'mod1';
  var ENERGY_METER_DATA = 'mod3';
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
    var formatted = parseFloat(num).toFixed(decimals);
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

  var navBarOpts = {
    el: '#navBar',
    mods: global.user_data.shipMods,
    currentMod: global.module
  };
  var navBar = global.NavBar.init(navBarOpts).mount();
  var breadcrumbOpts = {
    el: '#breadcrumbs',
    breadcrumbs: global.user_data.breadcrumbs
  }
  var breadcrumbs = global.Breadcrumbs.init(breadcrumbOpts).mount();
  switch(global.module) {
    case OVERVIEW:
      var mainGraph = global.MainGraph.init(_.extend({ el: '#mainGraph' }, global.graph)).mount();
      mainGraph.graph();

      var consumptionTableOpts = {
        el: '#metricsTable',
        metrics: {
          'Consumption(kWh) Avg per Day': formatNumber(global.metrics.values.kWh_day, 0, '', ' kWh'),
          'On-Peak Demand': formatNumber(global.metrics.values.Peak_Demand, 0, '', ' kW'),
          'Cost per Day': formatMoney(global.metrics.cost.Grand_Total_Lay_Day, 0),
          'Lay Days': formatNumber(global.metrics.values.Lay_Days, 2, '', ' days')
        },
        title: 'Consumption and Usage Summary'
      };

      global.ConsumptionTable.init(consumptionTableOpts).mount();
      break;
    case POWER_AND_COST_ANALYSIS:
      var sDate = moment.tz(global.graph.date_start, global.graph.timezone);
      var eDate = moment.tz(global.graph.date_end, global.graph.timezone);
      var formattedStartDate = sDate.format(SUBTITLE_DATE_FORMAT);
      var formattedEndDate = eDate.format(SUBTITLE_DATE_FORMAT);

      var powerCostGraphOpts = {
        el: '#mainGraph',
        subtitle: formattedStartDate + ' ' + formattedEndDate,
        pointInterval: global.graph.log_interval,
        pointStart: momentValueOfOffset(sDate, global.graph.timezone),
        plotBands: global.graph.peak_times,
        data: global.graph.data,
        timezone: global.graph.timezone
      };
      var powerCostGraph = global.PowerCostGraph.init(powerCostGraphOpts).mount();
      powerCostGraph.graph();
      break;
    case ENERGY_METER_DATA:
      var sDate = moment.tz(global.graph.date_start, global.graph.timezone);
      var eDate = moment.tz(global.graph.date_end, global.graph.timezone);
      var formattedStartDate = sDate.format(SUBTITLE_DATE_FORMAT);
      var formattedEndDate = eDate.format(SUBTITLE_DATE_FORMAT);

      var energyMeterGraphOpts = {
        el: '#mainGraph',
        subtitle: formattedStartDate + ' to ' + formattedEndDate,
        pointInterval: global.graph.log_interval,
        pointStart: momentValueOfOffset(sDate, global.graph.timezone),
        data: global.graph.data,
        units: global.graph.units,
        timezone: global.graph.timezone
      };
      var energyMeterGraph = global.EnergyMeterGraph.init(energyMeterGraphOpts).mount();
      energyMeterGraph.graph();
      global.DataExportForm.init({el: '#rawDataExport'}).mount();
      break;
  }
})($, _, m, moment, window);
