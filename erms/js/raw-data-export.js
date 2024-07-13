(function(m, global, $) {
  var setupJQueryTimePicker = function () {
    $('#data_export_start_date_time').datetimepicker({
      maxDate: new Date(),
        dateFormat: 'yy-mm-dd',
        controlType: 'select',
      timeFormat: 'HH:mm:ss'
    });

    $('#data_export_stop_date_time').datetimepicker({
      maxDate: new Date(),
        dateFormat: 'yy-mm-dd',
      controlType: 'select',
      timeFormat: 'HH:mm:ss'
    });

    $('#rawDataExport button').button();
  }

  var DataExportModel = {
    download: function () {
      return m.request({
        method: 'POST',
        url: '//' + window.location.host + '/erms/utils/data-export.php',
        data: DataExportModel.current
      }).then(function (result) {
        if(!result.error) {
          DataExportModel.error = '';
          window.location = result.data_url;
        } else {
          DataExportModel.error = result.error;
        }
      });
    },
    options: function () {
      var ships = this.current.ships_list;
      var options = ships.map(function (ship) {
        var name = DataExportModel.current.ships_data[ship].title
        return m("option", { value: ship }, name);
      })

      return [
        m("label", { style: { display: "block" }}, "Select Ship"),
        m("select", {
          oninput: m.withAttr("value", function (value) { DataExportModel.current.ship = value; })
        }, options)
      ];
    },
    current: {
      ship: "",
      shipClass: global.shipClass,
      aquisuite: global.aquisuite,
      user: global.user,
      ships_data: global.ships_data,
      ships_list: Object.keys(global.ships_data)
    },
    error: ""
  }

  var DataExportForm = {
    oninit: function () {
      DataExportModel.current.ship = DataExportModel.current.ships_list[0];
    },
    oncreate: function () {
      setupJQueryTimePicker();
    },
    onupdate: function () {
      setupJQueryTimePicker();
    },
    // view: function () {
    //   var inputs = [
    //     m("label[for=data_export_start_date_time]", { style: { display: "block" } }, "Start Date/Time"),
    //     m("input#data_export_start_date_time[type=text][name=data_export_start_date_time]", {
    //       onchange: function(e) { DataExportModel.current.data_export_start_date_time = e.target.value; },
    //       value: DataExportModel.current.data_export_start_date_time
    //     }),
    //     m("label[for=data_export_stop_date_time]", { style: { display: "block" } }, "End Date/Time"),
    //     m("input#data_export_stop_date_time[type=text][name=data_export_stop_date_time]", {
    //       onchange: function(e) { DataExportModel.current.data_export_stop_date_time = e.target.value; },
    //       value: DataExportModel.current.data_export_stop_date_time
    //     }),
    //     m("button[type=submit]", { style: { display: "block" } }, "Export Data")
    //   ];

    //   if(DataExportModel.current.ships_list.length > 1) {
    //     inputs.unshift(DataExportModel.options())
    //   }

    //   var selectionForm = [
    //       m("form", {
    //         style: {
    //           marginTop: "1em",
    //           marginBottom: "1em"
    //         },
    //         onsubmit: function (e) {
    //           e.preventDefault();
    //           DataExportModel.download()
    //         }
    //       }, inputs)
    //     ];

    //   if(DataExportModel.error) {
    //     selectionForm.unshift(m("div", { style: { color: "red", display: "block" } }, DataExportModel.error));
    //   }

    //   return m("div", { class: "iwbox1", style: { width: "200px" } }, [
    //     m("div", { id: "graph_range_sel_header" }, [
    //       m("span", "Raw Data Export")
    //     ]),
    //     m("div", { id: "date_time_panel" }, selectionForm)
    //   ])
    // }
  };

  global.DataExportForm = {
    init: function (opts) {
      this.el = opts.el;
      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), DataExportForm);
      return this;
    }
  };
})(m, window, jQuery);
