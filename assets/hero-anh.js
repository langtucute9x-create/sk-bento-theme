/* Đầu trang ảnh tấm ốp thật: nhuộm màu LED, đổi bộ tấm ốp, chuyển động theo chuột / cuộn trang */
(function () {
	var hero = document.getElementById('hero');
	var stage = document.getElementById('hstage');
	if (!hero || !stage) return;
	var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* ---- Nhuộm màu: chuyển độ sáng của ảnh sang màu đã chọn (vùng nhựa tối vẫn tối) ---- */
	var mx = document.getElementById('ledMx');
	var COLORS = {
		rainbow: null,
		tim: [0.66, 0.26, 1.0, 'rgba(160,70,255,.34)'],
		xanh: [0.16, 0.5, 1.0, 'rgba(50,130,255,.32)'],
		'do': [1.0, 0.16, 0.28, 'rgba(255,40,70,.32)'],
		trang: [1.0, 0.92, 0.8, 'rgba(255,235,210,.22)']
	};
	var ID = [1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0];
	function tint(c) {
		if (!c) return ID.slice();
		var k = 2.2, L = [0.299, 0.587, 0.114], m = [];
		for (var i = 0; i < 3; i++) m.push(k * c[i] * L[0], k * c[i] * L[1], k * c[i] * L[2], 0, 0);
		return m.concat([0, 0, 0, 1, 0]);
	}
	var cur = ID.slice(), anim = 0;
	function setMatrix(target) {
		if (!mx) return;
		var from = cur.slice(), t0 = performance.now(), dur = reduce ? 0 : 550;
		cancelAnimationFrame(anim);
		(function step(now) {
			var p = dur ? Math.min(1, (now - t0) / dur) : 1, e = p * (2 - p);
			cur = from.map(function (v, i) { return v + (target[i] - v) * e; });
			mx.setAttribute('values', cur.map(function (v) { return v.toFixed(4); }).join(' '));
			if (p < 1) anim = requestAnimationFrame(step);
		})(t0);
	}
	function pick(name) {
		var c = COLORS[name] || null;
		hero.setAttribute('data-led', name);
		stage.style.setProperty('--glow', c ? c[3] : 'rgba(170,70,255,.30)');
		setMatrix(tint(c));
	}
	var sw = hero.querySelectorAll('.led-swatch');
	sw.forEach(function (b) {
		b.addEventListener('click', function () {
			sw.forEach(function (o) { o.setAttribute('aria-pressed', o === b ? 'true' : 'false'); });
			pick(b.getAttribute('data-led'));
		});
	});

	/* ---- Đổi tấm ốp: mỗi lần đổi một vị trí, luân phiên, đầu trang không bao giờ trống ---- */
	var sets = [];
	try { sets = JSON.parse(stage.getAttribute('data-sets') || '[]'); } catch (e) {}
	var slots = [].slice.call(stage.querySelectorAll('.hp'));
	var visible = true;
	var pools = slots.map(function (el) {
		var k = el.getAttribute('data-slot'), seen = {}, list = [];
		sets.forEach(function (s) { var it = s[k]; if (it && !seen[it.u]) { seen[it.u] = 1; list.push(it); } });
		return { el: el, img: el.querySelector('img'), list: list, i: 0 };
	}).filter(function (p) { return p.list.length > 1; });
	var small = window.matchMedia('(max-width: 760px)').matches;
	function urlOf(it) { return small && it.m ? it.m : it.u; }
	function preload(it) { var i = new Image(); i.src = urlOf(it); }
	pools.forEach(function (p) { preload(p.list[1]); });
	var turn = 0;
	function swap() {
		if (!visible || document.hidden || !pools.length) return;
		var p = pools[turn % pools.length]; turn++;
		p.i = (p.i + 1) % p.list.length;
		var it = p.list[p.i], img = p.img;
		img.classList.add('out');
		setTimeout(function () {
			var done = function () { img.classList.remove('out'); };
			img.onload = done; img.onerror = done;
			img.removeAttribute('src');
			img.removeAttribute('srcset');
			img.width = it.w; img.height = it.h;
			if (it.m) img.srcset = it.m + ' ' + it.mw + 'w, ' + it.u + ' ' + it.w + 'w';
			img.src = it.u;
			if (img.complete) done();
			preload(p.list[(p.i + 1) % p.list.length]);
		}, reduce ? 0 : 650);
	}
	if (pools.length) setInterval(swap, 2600);

	/* ---- Chuyển động theo chuột và cuộn trang ---- */
	if ('IntersectionObserver' in window) {
		new IntersectionObserver(function (en) { visible = en[0].isIntersecting; if (visible) kick(); }).observe(hero);
	}
	if (reduce) { kick = function () {}; return; }
	var parts = slots.map(function (el) {
		return { el: el, d: parseFloat(el.getAttribute('data-d')) || .5, base: getComputedStyle(el).transform };
	});
	var tx = 0, ty = 0, cx = 0, cy = 0, raf = 0;
	if (window.matchMedia('(pointer: fine)').matches) {
		window.addEventListener('pointermove', function (e) {
			tx = e.clientX / window.innerWidth * 2 - 1;
			ty = e.clientY / window.innerHeight * 2 - 1;
			kick();
		}, { passive: true });
	}
	window.addEventListener('scroll', function () { kick(); }, { passive: true });
	window.addEventListener('resize', function () {
		parts.forEach(function (p) { p.el.style.transform = ''; p.base = getComputedStyle(p.el).transform; });
		kick();
	});
	function kick() { if (!raf && visible) raf = requestAnimationFrame(frame); }
	function frame() {
		raf = 0;
		cx += (tx - cx) * .08; cy += (ty - cy) * .08;
		var sy = Math.min(window.scrollY, window.innerHeight);
		parts.forEach(function (p) {
			var d = p.d, b = p.base === 'none' ? '' : p.base;
			p.el.style.transform = 'perspective(1000px) translate3d(' + (cx * d * 26).toFixed(2) + 'px,' +
				(cy * d * 16 - sy * d * .22).toFixed(2) + 'px,0) rotateY(' + (cx * d * 7).toFixed(2) + 'deg) rotateX(' +
				(-cy * d * 5).toFixed(2) + 'deg) ' + b;
		});
		if (Math.abs(tx - cx) > .001 || Math.abs(ty - cy) > .001) kick();
	}
	kick();
})();
