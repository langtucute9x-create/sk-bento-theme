/**
 * SK Bento — tương tác chung (v0.10).
 * Ý tưởng hình thức tham khảo kho Lucian UX/UI prompt (MIT, https://github.com/Seokwoooo/lucian-uxui-prompt):
 * #212 header ẩn khi cuộn, #251 hiện dần khi cuộn, #228 viền sáng theo con trỏ, #258 ảnh hiện dần,
 * #244 form dẫn tới ô lỗi, #286 menu điện thoại dạng tấm. Viết lại bằng mã của theme, không dùng màu --ux-*.
 * Tôn trọng "giảm chuyển động" của máy; máy không chạy JS vẫn xem đủ nội dung.
 */
(function () {
  var doc = document.documentElement;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  doc.classList.add('ux');
  /* Đa ngôn ngữ (0.14): chữ dịch + đổi đường dẫn sang ngôn ngữ đang xem (inc/da-ngu.php) */
  var K = window.SKB || {};
  var T = function (s) { return (K.t && K.t[s]) || s; };
  var L = function (u) { if (!K.lang || K.lang === 'vi' || !K.goc || String(u).indexOf(K.goc + '/') !== 0) return u; return K.goc + '/' + K.lang + String(u).slice(K.goc.length); };

  /* ---- Menu điện thoại: tấm trượt, đóng khi bấm ra ngoài / Esc / chọn mục ---- */
  var btn = document.querySelector('.menu-toggle');
  var panel = document.getElementById('mobile-panel');
  if (btn && panel) {
    var setOpen = function (open) {
      panel.classList.toggle('open', open);
      document.body.classList.toggle('menu-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.setAttribute('aria-label', open ? T('Đóng menu') : T('Mở menu'));
      if (open) { var a = panel.querySelector('a'); if (a) a.focus({ preventScroll: true }); }
    };
    btn.addEventListener('click', function (e) { e.stopPropagation(); setOpen(!panel.classList.contains('open')); });
    document.addEventListener('click', function (e) {
      if (panel.classList.contains('open') && !panel.contains(e.target)) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panel.classList.contains('open')) { setOpen(false); btn.focus(); }
    });
    panel.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
  }

  /* ---- Header: dính trên đầu, nền mờ khi đã cuộn, ẩn khi cuộn xuống, hiện khi cuộn lên ---- */
  var header = document.querySelector('.site-header');
  if (header) {
    var lastY = window.scrollY, ticking = false;
    var onScroll = function () {
      var y = window.scrollY, h = header.offsetHeight || 80;
      header.classList.toggle('is-stuck', y > h);
      var down = y > lastY + 4, up = y < lastY - 4;
      if (y > h * 4 && down && !document.body.classList.contains('menu-open')) header.classList.add('is-hidden');
      else if (up || y <= h * 4) header.classList.remove('is-hidden');
      lastY = y; ticking = false;
    };
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
    header.addEventListener('focusin', function () { header.classList.remove('is-hidden'); });
    onScroll();
  }

  /* ---- Thanh chip hãng / dòng xe: đưa mục đang chọn vào giữa ---- */
  Array.prototype.forEach.call(document.querySelectorAll('.chips'), function (bar) {
    var on = bar.querySelector('.chip.on');
    if (on) bar.scrollLeft = on.offsetLeft - (bar.clientWidth - on.offsetWidth) / 2;
  });

  /* ---- Ảnh hiện dần khi tải xong (ảnh đã có sẵn thì hiện ngay) ---- */
  var fadeImgs = document.querySelectorAll('.post-card img, .dealer-img img, .gallery2 img, .mx-main img, .mx-th img, .entry img');
  Array.prototype.forEach.call(fadeImgs, function (img) {
    if (img.complete && img.naturalWidth) return;
    img.classList.add('fade-img');
    var done = function () { img.classList.add('is-loaded'); };
    img.addEventListener('load', done, { once: true });
    img.addEventListener('error', done, { once: true });
  });

  /* ---- Hiện dần khi cuộn tới (chỉ phần còn ở dưới màn hình lúc mở trang) ---- */
  if (!reduce && 'IntersectionObserver' in window) {
    var sel = '.features .feat, .sec-head, .gallery2 > *, .activate-band, .brands .brand, .dealers .dealer, .posts .post-card, .cta2 > *, .mx-desc > *, .lh-grid > *, .lh-map, .kh-grid > *, .entry > h2';
    var vh = window.innerHeight;
    var items = Array.prototype.filter.call(document.querySelectorAll(sel), function (el) {
      return el.getBoundingClientRect().top > vh * 0.92;
    });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        en.target.classList.add('in');
        io.unobserve(en.target);
      });
    }, { rootMargin: '0px 0px -8% 0px' });
    items.forEach(function (el) {
      var sib = el.parentNode ? Array.prototype.indexOf.call(el.parentNode.children, el) : 0;
      el.style.setProperty('--rv-d', (Math.min(sib % 6, 5) * 70) + 'ms');
      el.classList.add('rv');
      io.observe(el);
    });
  }

  /* ---- Viền sáng theo con trỏ trên thẻ (chỉ máy có chuột) ---- */
  if (!reduce && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    var glowSel = '.post-card, .dealer, .brand, .tile, .lh-list, .kh-side, .mx-spec, .chip';
    Array.prototype.forEach.call(document.querySelectorAll(glowSel), function (el) { el.classList.add('glow'); });
    var last = null;
    document.addEventListener('pointermove', function (e) {
      var el = e.target.closest && e.target.closest('.glow');
      if (last && last !== el) last.classList.remove('glow-on');
      last = el;
      if (!el) return;
      var r = el.getBoundingClientRect();
      el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
      el.style.setProperty('--my', (e.clientY - r.top) + 'px');
      el.classList.add('glow-on');
    }, { passive: true });
  }

  /* ---- Form đặt lịch: kiểm trước khi gửi, báo lỗi ngay tại ô và đưa tới ô lỗi đầu tiên ---- */
  Array.prototype.forEach.call(document.querySelectorAll('#dat-lich-form form'), function (form) {
    var rules = [
      { name: 'ho_ten', test: function (v) { return v.trim().length >= 2; }, msg: 'Nhập họ và tên.' },
      { name: 'dien_thoai', test: function (v) { return /^[0-9 +.]{9,15}$/.test(v.trim()) && v.replace(/\D/g, '').length >= 9; }, msg: 'Số điện thoại gồm 9–15 chữ số.' }
    ];
    var showErr = function (input, msg) {
      var id = input.id + '-err', box = document.getElementById(id);
      if (!msg) { input.removeAttribute('aria-invalid'); if (box) box.remove(); return; }
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', id);
      if (!box) { box = document.createElement('p'); box.id = id; box.className = 'field-err'; input.insertAdjacentElement('afterend', box); }
      box.textContent = msg;
    };
    rules.forEach(function (r) {
      var input = form.elements[r.name]; if (!input) return;
      input.addEventListener('blur', function () { if (input.value) showErr(input, r.test(input.value) ? '' : r.msg); });
      input.addEventListener('input', function () { if (input.getAttribute('aria-invalid')) showErr(input, r.test(input.value) ? '' : r.msg); });
    });
    form.setAttribute('novalidate', '');
    form.addEventListener('submit', function (e) {
      var first = null;
      rules.forEach(function (r) {
        var input = form.elements[r.name]; if (!input) return;
        var ok = r.test(input.value); showErr(input, ok ? '' : r.msg);
        if (!ok && !first) first = input;
      });
      if (first) {
        e.preventDefault();
        first.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
        first.focus({ preventScroll: true });
        return;
      }
      var sub = form.querySelector('button[type=submit]');
      if (sub) { sub.disabled = true; sub.textContent = 'Đang gửi…'; }
    });
  });

  /* ---- Bản đồ đại lý 3D: nghiêng theo chuột, dừng hiệu ứng khi khuất màn hình ---- */
  Array.prototype.forEach.call(document.querySelectorAll('[data-vnmap]'), function (fig) {
    var svg = fig.querySelector('svg'); if (!svg) return;
    var run = function (on) {
      fig.classList.toggle('vn-paused', !on);
      if (svg.pauseAnimations) { if (on && !reduce) svg.unpauseAnimations(); else svg.pauseAnimations(); }
    };
    run(!reduce);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (es) { run(es[0].isIntersecting); }).observe(fig);
    }
    if (reduce || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    fig.addEventListener('pointermove', function (e) {
      var r = fig.getBoundingClientRect();
      var x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
      svg.style.setProperty('--ry', (x * 8).toFixed(2) + 'deg');
      svg.style.setProperty('--rx', (-y * 6).toFixed(2) + 'deg');
    }, { passive: true });
    fig.addEventListener('pointerleave', function () { svg.style.removeProperty('--ry'); svg.style.removeProperty('--rx'); });
  });

  /* ---- Đo lượt bấm: gửi về web (inc/su-kien.php), có GA4 thì gửi thêm ---- */
  var cfg = window.SKB || {};
  var me = document.body.classList.contains('logged-in');
  var ev = window.skbEv = function (l, m) {
    m = String(m || '').slice(0, 180);
    if (window.gtag) try { window.gtag('event', 'sk_' + l, { muc: m }); } catch (e) {}
    if (me || !cfg.ev) return;
    var fd = new FormData(); fd.append('action', 'skb_ev'); fd.append('l', l); fd.append('m', m);
    try { if (!(navigator.sendBeacon && navigator.sendBeacon(cfg.ev, fd))) fetch(cfg.ev, { method: 'POST', body: fd, keepalive: true }); } catch (e) {}
  };
  var trang = function () { try { return decodeURIComponent(location.pathname).replace(/^\/moitest/, '') || '/'; } catch (e) { return location.pathname; } };
  var h1 = document.querySelector('h1');
  var pageName = h1 ? h1.textContent.trim().replace(/\s+/g, ' ') : trang();
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a, .vn-pin, .vn-chip');
    if (!a) return;
    var href = a.getAttribute('href') || '';
    if (/^tel:/i.test(href)) ev('goi', pageName);
    else if (/zalo\.me/i.test(href)) ev('zalo', pageName);
    else if (a.classList.contains('dir')) { var d = a.closest('.dealer'); ev('chi_duong', d ? (d.querySelector('.name') || d).textContent.trim() : ''); }
    else if (/catalog\.sklight\.vn/i.test(href)) ev('mo_catalog', pageName);
    else if (a.classList.contains('vn-pin') || a.classList.contains('vn-chip')) ev('ban_do', a.getAttribute('data-tinh') || '');
  }, true);
  document.addEventListener('change', function (e) {
    var r = e.target;
    if (r.matches && r.matches('.mx-goi input') && r.checked) ev('chon_goi', pageName + ' — ' + (r.dataset.ten || ''));
  });
  if (document.body.classList.contains('single-mau_xe')) ev('xem_mau', pageName);
  if (document.getElementById('dat-lich-form')) ev('mo_dl', trang());
  try { if (/[?&]nguon=catalog\b/.test(location.search) && !sessionStorage.getItem('skb_tu_cat')) { sessionStorage.setItem('skb_tu_cat', '1'); ev('tu_catalog', trang()); } } catch (e) {}

  /* ---- Video YouTube: ảnh bìa, bấm mới tải trình phát ---- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('.yt-play');
    if (!b) return;
    var box = b.parentNode, id = box.getAttribute('data-yt');
    var f = document.createElement('iframe');
    f.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0&playsinline=1';
    f.title = b.getAttribute('aria-label') || 'Video';
    f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
    f.allowFullscreen = true;
    box.classList.add('on');
    box.replaceChild(f, b);
    ev('video', (b.getAttribute('aria-label') || '').replace(/^[^:]*:\s*/, '') || id);
  });

  /* ---- Xem ảnh lớn (ảnh khách đã lắp) ---- */
  var lbLinks = document.querySelectorAll('[data-lb]');
  if (lbLinks.length && window.HTMLDialogElement) {
    var dlg = document.createElement('dialog');
    dlg.className = 'lb';
    dlg.innerHTML = '<button type="button" class="lb-x" aria-label="' + T('Đóng') + '">×</button><button type="button" class="lb-p" aria-label="' + T('Ảnh trước') + '">‹</button><img alt=""><button type="button" class="lb-n" aria-label="' + T('Ảnh sau') + '">›</button>';
    document.body.appendChild(dlg);
    var img = dlg.querySelector('img'), cur = 0, group = [];
    var show = function (i) { cur = (i + group.length) % group.length; img.src = group[cur].href; img.alt = group[cur].getAttribute('aria-label') || ''; };
    Array.prototype.forEach.call(lbLinks, function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        group = Array.prototype.slice.call(document.querySelectorAll('[data-lb="' + a.getAttribute('data-lb') + '"]'));
        show(group.indexOf(a)); dlg.showModal();
      });
    });
    dlg.querySelector('.lb-x').onclick = function () { dlg.close(); };
    dlg.querySelector('.lb-p').onclick = function () { show(cur - 1); };
    dlg.querySelector('.lb-n').onclick = function () { show(cur + 1); };
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    dlg.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') show(cur - 1); if (e.key === 'ArrowRight') show(cur + 1); });
  }

  /* ---- Ô "Xe của bạn là gì?": gợi ý tức thì, không dấu, phím mũi tên ---- */
  var fold = function (s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'd').toLowerCase(); };
  var flat = function (s) { return fold(s).replace(/[^a-z0-9]/g, ''); };
  var xeData = null, xeLoad = null;
  var loadXe = function (src) {
    if (xeData) return Promise.resolve(xeData);
    if (!xeLoad) xeLoad = fetch(src, { credentials: 'omit' }).then(function (r) { return r.json(); }).then(function (d) {
      xeData = (Array.isArray(d) ? d : []).map(function (x) {
        x._f = flat(x.t + ' ' + x.k); x._w = fold(x.t + ' ' + x.k);
        if (K.lang && K.lang !== 'vi') { x.u = L(x.u); if (x.p) { var pn = parseInt(String(x.p).replace(/\D/g, ''), 10); if (pn) x.p = pn.toLocaleString(K.hl || 'en').replace(/[\s\u00a0\u202f]/g, K.tach || ',') + ' ₫'; } if (x.h) x.t = x.t.indexOf('Hãng ') === 0 ? T('Hãng') + ' ' + T(x.t.slice(5)) : T(x.t); }
        return x;
      });
      return xeData;
    }).catch(function () { xeLoad = null; return []; });
    return xeLoad;
  };
  Array.prototype.forEach.call(document.querySelectorAll('[data-tim-xe]'), function (box) {
    var input = box.querySelector('input'), list = box.querySelector('.tim-xe-list'), items = [], act = -1, timer = null, lastQ = '';
    var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    var close = function () { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); act = -1; };
    var setAct = function (i) {
      var lis = list.querySelectorAll('[role=option]');
      if (!lis.length) return;
      act = (i + lis.length) % lis.length;
      Array.prototype.forEach.call(lis, function (li, j) { li.setAttribute('aria-selected', j === act ? 'true' : 'false'); });
      input.setAttribute('aria-activedescendant', lis[act].id);
      lis[act].scrollIntoView({ block: 'nearest' });
    };
    var render = function () {
      var q = input.value.trim(), fq = flat(q);
      if (fq.length < 1) { close(); return; }
      var toks = fold(q).split(/[^a-z0-9]+/).filter(Boolean);
      items = xeData.map(function (x) {
        var ok = x._f.indexOf(fq) !== -1 || toks.every(function (t) { return x._f.indexOf(t) !== -1; });
        if (!ok) return null;
        var score = (x._f.indexOf(fq) !== -1 ? 10 : 0) + (flat(x.t).indexOf(fq) === 0 ? 5 : 0) + (x.h ? 3 : 0) - x.t.length / 100;
        return { x: x, s: score };
      }).filter(Boolean).sort(function (a, b) { return b.s - a.s; }).slice(0, 8).map(function (o) { return o.x; });
      var base = input.id + '-o';
      list.innerHTML = items.length ? items.map(function (x, i) {
        var meta = x.h ? (x.n ? T('%d mẫu').replace('%d', x.n) : T('Xem các mẫu')) : (x.p ? T('Từ %s').replace('%s', x.p) : T('Liên hệ')) + (x.g ? ' · ' + T('%d gói').replace('%d', x.g) : '');
        return '<li role="option" id="' + base + i + '" aria-selected="false" data-i="' + i + '">' +
          (x.i ? '<img src="' + esc(x.i) + '" alt="" loading="lazy" width="44" height="44">' : '<span class="tx-ic' + (x.h ? ' h' : '') + '" aria-hidden="true"></span>') +
          '<span class="tx-t">' + esc(x.t) + '</span><span class="tx-m">' + esc(meta) + '</span></li>';
      }).join('') : (K.wa ? '<li class="tx-none">' + esc(T('Chưa có mẫu cho "%s".').replace('%s', q)) + ' <a href="' + esc(K.wa) + '" target="_blank" rel="noopener">' + esc(T('Nhắn WhatsApp')) + '</a></li>'
        : '<li class="tx-none">Chưa có mẫu cho "' + esc(q) + '". <a href="tel:' + esc(cfg.tel || '') + '">Gọi tư vấn</a> hoặc <a href="' + esc((cfg.home || '/') + 'dat-lich/?xe=' + encodeURIComponent(q)) + '">đặt lịch</a> để SK báo mẫu cho xe của bạn.</li>');
      list.hidden = false; input.setAttribute('aria-expanded', 'true'); act = -1;
      if (q !== lastQ && q.length >= 3) { clearTimeout(timer); timer = setTimeout(function () { ev('tim_xe', q.toLowerCase()); }, 1500); lastQ = q; }
    };
    var go = function (i) { var x = items[i]; if (!x) return; clearTimeout(timer); ev('tim_xe', input.value.trim().toLowerCase() + ' → ' + x.t); location.href = x.u; };
    input.addEventListener('focus', function () { loadXe(box.getAttribute('data-src')).then(function () { if (input.value.trim()) render(); }); }, { once: false });
    input.addEventListener('input', function () { loadXe(box.getAttribute('data-src')).then(render); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); if (list.hidden) render(); setAct(act + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); setAct(act - 1); }
      else if (e.key === 'Enter') { e.preventDefault(); go(act >= 0 ? act : 0); }
      else if (e.key === 'Escape') { close(); }
    });
    list.addEventListener('mousedown', function (e) { var li = e.target.closest('[data-i]'); if (li) { e.preventDefault(); go(+li.getAttribute('data-i')); } });
    document.addEventListener('click', function (e) { if (!box.contains(e.target)) close(); });
  });
})();
