(function(m, _, global) {
  var NavSubBarModel = {};
  var NavSubBar = {
    view: function () {
      return m('div', { class: 'navsubbar' }, [
        m('ul', { class: 'navsubbar__list' }, _.map(NavSubBarModel.permittedShipClasses, function (group, cls) {
            var linkClass = 'navsubbar__link';
            if(cls === NavSubBarModel.currentClass) {
              linkClass = [linkClass, linkClass + '--selected'].join(' ');
            }
            return m('li', { class: 'navsubbar__item' }, m('a', { class: linkClass, href: group.homepage }, group.name));
        }))
      ]);
    }
  };

  global.NavSubBar = {
    init: function (opts) {
      this.el = opts.el;
      _.extend(NavSubBarModel, _.omit(opts, 'el'));
      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), NavSubBar);
    }
  };
})(m, _, window);
