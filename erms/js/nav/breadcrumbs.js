(function(m, _, global) {

  var BreadcrumbsModel = {};
  var Breadcrumbs = {
    view: function () {
      return m('div', { class: 'breadcrumbs' },
        m('ul', { class: 'breadcrumbs__list' }, _.map(BreadcrumbsModel.breadcrumbs, function (crumb, index) {
          if(index === (BreadcrumbsModel.breadcrumbs.length - 1)) {
            return m('li', { class: 'breadcrumbs__item' }, [
              m('span', { class: 'breadcrumbs__indicator' }, crumb.indicator),
              m('span', { class: 'breadcrumbs__module' }, crumb.module)
            ]);
          }
          return m('li', { class: 'breadcrumbs__item' }, [
            m('a', { class: 'breadcrumbs__link', href: crumb.link }, crumb.text ),
            m('div', { class: 'breadcrumbs__arrow' })
          ]);
        }))
      );
    }
  };
  global.Breadcrumbs = {
    init: function (opts) {
      this.el = opts.el;
      _.extend(BreadcrumbsModel, _.omit(opts, 'el'));
      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), Breadcrumbs);
    }
  };
})(m, _, window);
