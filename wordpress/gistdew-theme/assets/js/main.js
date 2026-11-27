/* GistDew theme script */
(function () {
  var root = document.documentElement;

  // Dark mode toggle (remembers the choice on this device)
  var tt = document.querySelector('.theme-toggle');
  if (tt) {
    tt.addEventListener('click', function () {
      var dark = root.getAttribute('data-theme') === 'dark' ||
        (!root.getAttribute('data-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
      var next = dark ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('gd-theme', next); } catch (e) {}
    });
  }

  // Mobile menu
  var nav = document.getElementById('primary-nav');
  var mt = document.querySelector('.menu-toggle');
  var bd = document.querySelector('.nav-backdrop');
  var nc = document.querySelector('.nav-close');
  function setMenu(open) {
    if (!nav) return;
    nav.classList.toggle('open', open);
    if (bd) bd.classList.toggle('open', open);
    if (mt) mt.setAttribute('aria-expanded', open ? 'true' : 'false');
    root.classList.toggle('menu-open', open);
    document.body.style.overflow = open ? 'hidden' : '';
  }
  if (mt) mt.addEventListener('click', function () { setMenu(true); });
  if (nc) nc.addEventListener('click', function () { setMenu(false); });
  if (bd) bd.addEventListener('click', function () { setMenu(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setMenu(false); });

  // Search panel
  var st = document.querySelector('.search-toggle');
  var sp = document.getElementById('search-panel');
  if (st && sp) {
    st.addEventListener('click', function () {
      var open = !sp.classList.contains('open');
      sp.classList.toggle('open', open);
      st.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) { var i = sp.querySelector('input[type=search]'); if (i) i.focus(); }
    });
  }

  // Copy link
  var cl = document.querySelector('.copy-link');
  if (cl) {
    cl.addEventListener('click', function () {
      var url = cl.getAttribute('data-url');
      var done = function () { var t = cl.textContent; cl.textContent = 'Copied ✓'; setTimeout(function () { cl.textContent = t; }, 1600); };
      if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, function () {});
    });
  }

  // Count a view once per post per day (used for Trending)
  if (window.GistDew && Number(GistDew.postId) > 0) {
    var key = 'gd-v-' + GistDew.postId + '-' + new Date().toISOString().slice(0, 10);
    var seen = false;
    try { seen = !!localStorage.getItem(key); } catch (e) {}
    if (!seen) {
      setTimeout(function () {
        fetch(GistDew.viewUrl + GistDew.postId, { method: 'POST', credentials: 'omit' }).catch(function () {});
        try { localStorage.setItem(key, '1'); } catch (e) {}
      }, 4000);
    }
  }
})();
