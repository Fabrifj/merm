(function(m, _, global) {
  var ConsumptionTableModel = {};

  var ConsumptionTable = {
    view: function () {
      return m("div", { class: "wrapper" }, [
        m("div", { class: "consumption_box_group", style: { width: "990px" }}, [
          m("div", { id: "graph_range_sel_header" }, [
            m("span", { style: { fontWeight: "bold" }}, ConsumptionTableModel.title)
          ]),
          m("div", [
            m("table", { id: "group_table" }, _.map(ConsumptionTableModel.metrics, function (value, key) {
                return m("tr", [
                  m("td", key),
                  m("td", value)
                ]);
              })
            )
          ])
        ])
      ]);
    }
  };

  global.ConsumptionTable = {
    init: function (opts) {
      this.el = opts.el;
      _.extend(ConsumptionTableModel, _.omit(opts, 'el'));
      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), ConsumptionTable);
    }
  };
})(m, _, window);
