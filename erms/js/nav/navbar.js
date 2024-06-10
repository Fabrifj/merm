(function(m, _, global) {
  var NavBarModel = {};
  var NavBar = {
    view: function () {
      return m('div', { class: 'navbar' }, [
        m('ul', { class: 'navbar__list' }, _.map(NavBarModel.mods, function (mod, key) {
            var linkClass = 'navbar__link';
            if(key === NavBarModel.currentMod) {
              linkClass = [linkClass, linkClass + '--selected'].join(' ');
            }
            return m('li', { class: 'navbar__item' }, m('a', { class: linkClass, href: mod.link }, mod.text));
        })),
        m('a', { class: 'navbar__logout', href: '/Auth/logout.php' }, "Logout")
      ]);
    }
  };

  global.NavBar = {
    init: function (opts) {
      this.el = opts.el;
      _.extend(NavBarModel, _.omit(opts, 'el'));
      return this;
    },
    mount: function () {
      m.mount(document.querySelector(this.el), NavBar);
    }
  };
})(m, _, window);
