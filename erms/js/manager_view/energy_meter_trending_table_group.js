(function(m, _, global) {
  var EnergyMeterTrendingTableGroupModel = {};

  var EnergyMeterTrendingTableGroup = {
    view: function () {
      return m('div', m('div', { class: 'consumption_box_group' }, [
          m('div', { id: 'graph_range_sel_header' }, [
            m('span', { style: { fontWeight: 'bold' }}, EnergyMeterTrendingTableGroupModel.title)
          ]),
          m('div', [
            m('table', { id: 'group_table' }, [
                m('tr',
                  _.reduce(EnergyMeterTrendingTableGroupModel.headers, function (memo, value, key) {
                    var attrs = value.hasData? { style: { color: '#000'}}: { style: { color: '#FF7676'}};
                    memo.push(m('th',  m('a', { href: value.path, style: { textDecoration: 'none'}}, m('span', attrs, value.title))));
                    return memo;
                }, [m('th', String.fromCharCode(160))])),
                _.map(EnergyMeterTrendingTableGroupModel.metrics, function (value, key) {
                  var rowTitle = [m('td', key)];
                  _.each(value, function (v) {
                    rowTitle.push(m('td', v));
                  });
                  return m('tr', rowTitle);
                })
            ])
          ])
         ]));
    }
  };

  global.EnergyMeterTrendingTableGroup = {
    init: function (opts) {
      this.el = opts.el;
      _.extend(EnergyMeterTrendingTableGroupModel, _.omit(opts, 'el'));
      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), EnergyMeterTrendingTableGroup);
    }
  };
})(m, _, window);
