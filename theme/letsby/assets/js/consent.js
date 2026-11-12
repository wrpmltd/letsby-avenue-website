/* Cookie consent + Google Analytics 4, loaded only after "Accept".
   Choice kept 180 days in a first-party cookie (la_consent=analytics|essential).
   Any link to #cookie-settings reopens the panel. */
(function () {
  var NAME = 'la_consent', DAYS = 180, GA = (window.LETSBY || {}).ga4;

  function get() { var m = document.cookie.match(/(?:^|; )la_consent=([^;]+)/); return m ? m[1] : null; }
  function set(v) { document.cookie = NAME + '=' + v + '; Max-Age=' + DAYS * 86400 + '; Path=/; SameSite=Lax; Secure'; }

  function loadGA() {
    if (!GA || window.__laGA) return;
    window.__laGA = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { dataLayer.push(arguments); };
    gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'granted' });
    gtag('js', new Date());
    gtag('config', GA, { allow_google_signals: false, allow_ad_personalization_signals: false });
    var s = document.createElement('script');
    s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA;
    document.head.appendChild(s);
    // Count the enquiry thank-you page as a conversion.
    if (location.pathname.replace(/\/$/, '') === '/thank-you') gtag('event', 'generate_lead', { form: 'website_enquiry' });
  }

  function clearGA() {
    document.cookie.split('; ').forEach(function (c) {
      var n = c.split('=')[0];
      if (/^_ga/.test(n)) {
        var host = location.hostname.replace(/^www\./, '');
        ['', '.' + host, host].forEach(function (d) {
          document.cookie = n + '=; Max-Age=0; Path=/' + (d ? '; Domain=' + d : '');
        });
      }
    });
  }

  var panel;
  function build() {
    panel = document.createElement('div');
    panel.className = 'la-consent';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Cookie preferences');
    panel.innerHTML =
      '<div class="la-consent__text"><strong>Your privacy matters</strong>' +
      '<p>We use essential cookies to make our website work. With your permission, we\'d also like to use analytics cookies to understand how visitors use our website and improve our services. You can accept, reject or change your choice at any time. <a href="/cookie-policy">Cookie policy</a></p>' +
      '<label class="la-consent__toggle" hidden><input type="checkbox" data-la="analytics"> Analytics cookies (Google Analytics)</label></div>' +
      '<div class="la-consent__btns">' +
      '<button type="button" class="la-btn la-btn--red" data-la="accept">Accept all</button>' +
      '<button type="button" class="la-btn la-btn--red" data-la="reject">Reject non-essential</button>' +
      '<button type="button" class="la-btn la-btn--ghost" data-la="manage">Manage preferences</button>' +
      '<button type="button" class="la-btn la-btn--ghost" data-la="save" hidden>Save preferences</button></div>';
    document.body.appendChild(panel);
    panel.addEventListener('click', function (e) {
      var a = e.target.getAttribute && e.target.getAttribute('data-la');
      if (a === 'accept') choose('analytics');
      if (a === 'reject') choose('essential');
      if (a === 'manage') {
        panel.querySelector('.la-consent__toggle').hidden = false;
        panel.querySelector('[data-la=save]').hidden = false;
        e.target.hidden = true;
        panel.querySelector('[data-la=analytics]').checked = get() === 'analytics';
      }
      if (a === 'save') choose(panel.querySelector('[data-la=analytics]').checked ? 'analytics' : 'essential');
    });
  }

  function choose(v) {
    var was = get();
    set(v);
    hide();
    if (v === 'analytics') loadGA();
    else if (was === 'analytics') { clearGA(); location.reload(); }
  }
  function show() { if (!panel) build(); panel.hidden = false; }
  function hide() { if (panel) panel.hidden = true; }

  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href$="#cookie-settings"]');
    if (a) { e.preventDefault(); show(); }
  });

  var c = get();
  if (c === 'analytics') loadGA();
  else if (!c) show();
})();
