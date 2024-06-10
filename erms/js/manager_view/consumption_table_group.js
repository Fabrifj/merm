(function(m, _, global) {
  var ConsumptionTableGroupModel = {};

  var ConsumptionTableGroup = {
    view: function () {
      var reducedLayDays;
      if(!_.some(ConsumptionTableGroupModel.hasAllLayDays)) {
        reducedLayDays = m('div', { style: { marginBottom: '5px' }}, '*Reduced number of lay days');
      }
      return m('div', m('div', { class: 'consumption_box_group' }, [
          m('div', { id: 'graph_range_sel_header' }, [
            m('span', { style: { fontWeight: 'bold' }}, ConsumptionTableGroupModel.title),
            m('span', {
              class: 'export-to-excel',
              onclick: function () {
                global.write_to_excel('group_table');
              }
            }, m('i', { class: 'fa fa-download fa-2x' }))
          ]),
          m('div', [
            m('table', { id: 'group_table' }, [
                m('tr',
                  _.reduce(ConsumptionTableGroupModel.headers, function (memo, value, key) {
                    var attrs = value.hasData? { style: { color: '#000'}}: { style: { color: '#FF7676'}};
                    memo.push(m('th',  m('a', { href: value.path, style: { textDecoration: 'none'}}, m('span', attrs, value.title))));
                    return memo;
                }, [m('th', String.fromCharCode(160))])),
                _.map(ConsumptionTableGroupModel.metrics, function (value, key) {
                  var rowTitle = [m('td', key)];
                  _.each(value, function (v) {
                    rowTitle.push(m('td', v));
                  });
                  return m('tr', rowTitle);
                })
            ])
          ])
         ]), reducedLayDays);
    }
  };

  global.ConsumptionTableGroup = {
    init: function (opts) {
      this.el = opts.el;
      _.extend(ConsumptionTableGroupModel, _.omit(opts, 'el'));
      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), ConsumptionTableGroup);
    }
  };
})(m, _, window);
