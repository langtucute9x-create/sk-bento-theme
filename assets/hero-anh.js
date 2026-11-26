/* Đầu trang ảnh tấm ốp thật: nhuộm màu LED, đổi bộ tấm ốp, chuyển động theo chuột / cuộn trang */
(function () {
	var hero = document.getElementById('hero') || document.querySelector('.mx-op');
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

	/* ---- Đổi tấm ốp ----
	   Cách cũ (data-sets): trộn ảnh nhiều xe, mỗi lần đổi một vị trí.
	   0.22.0 (data-xe): mỗi lượt là một mẫu xe; trong một xe, vị trí nào có nhiều tấm cùng dáng thì lần lượt thay nhau;
	   hết lượt thì cả cụm mờ đi rồi hiện bộ tấm ốp của xe kế tiếp, kèm tên xe và liên kết. */
	var sets = [], cars = [];
	try { sets = JSON.parse(stage.getAttribute('data-sets') || '[]'); } catch (e) {}
	try { cars = JSON.parse(stage.getAttribute('data-xe') || '[]'); } catch (e) {}
	var byCar = cars.length > 0;
	var slots = [].slice.call(stage.querySelectorAll('.hp'));
	var visible = true;
	var all = slots.map(function (el) { return { el: el, img: el.querySelector('img'), k: el.getAttribute('data-slot'), list: [], i: 0 }; });
	var pools = [];
	var small = window.matchMedia('(max-width: 760px)').matches;
	function urlOf(it) { return small && it.m ? it.m : it.u; }
	function preload(it) { if (!it) return; var i = new Image(); i.src = urlOf(it); }
	function fill(ci) {
		all.forEach(function (p) {
			var list = [];
			if (byCar) list = (cars[ci].s && cars[ci].s[p.k]) || [];
			else { var seen = {}; sets.forEach(function (s) { var it = s[p.k]; if (it && !seen[it.u]) { seen[it.u] = 1; list.push(it); } }); }
			p.list = list; p.i = 0;
		});
		pools = all.filter(function (p) { return p.list.length > 1; });
		pools.forEach(function (p) { preload(p.list[1]); });
	}
	function show(p, it) {
		var img = p.img, done = function () { img.classList.remove('out'); };
		img.onload = done; img.onerror = done;
		img.removeAttribute('src');
		img.removeAttribute('srcset');
		img.width = it.w; img.height = it.h;
		if (it.m) img.srcset = it.m + ' ' + it.mw + 'w, ' + it.u + ' ' + it.w + 'w';
		img.src = it.u;
		if (img.complete) done();
	}
	fill(0);
	var turn = 0, busy = false;
	function swap() {
		if (busy || !visible || document.hidden || !pools.length) return;
		var p = pools[turn % pools.length]; turn++;
		p.i = (p.i + 1) % p.list.length;
		var it = p.list[p.i];
		p.img.classList.add('out');
		setTimeout(function () {
			if (busy) return;
			show(p, it);
			preload(p.list[(p.i + 1) % p.list.length]);
		}, reduce ? 0 : 650);
	}
	setInterval(swap, 2600);

	/* Đổi sang xe khác (chỉ khi có từ 2 xe) */
	if (byCar && cars.length > 1) {
		var ci = 0, cap = hero.querySelector('.hxe-a'), capB = cap && cap.querySelector('b');
		var dots = [].slice.call(hero.querySelectorAll('.hxe-dots button'));
		var auto = 0;
		var warm = function (n) { var c = cars[(n + cars.length) % cars.length]; for (var k in c.s) preload(c.s[k][0]); };
		var go = function (n) {
			n = (n + cars.length) % cars.length;
			if (n === ci || busy) return;
			busy = true; ci = n;
			all.forEach(function (p) { p.img.classList.add('out'); });
			if (cap) cap.classList.add('out');
			setTimeout(function () {
				fill(ci);
				all.forEach(function (p) {
					var it = p.list[0];
					p.el.classList.toggle('hp-off', !it);
					if (it) show(p, it); else p.img.classList.remove('out');
				});
				if (cap) { cap.href = cars[ci].u; if (capB) capB.textContent = cars[ci].t; cap.classList.remove('out'); }
				dots.forEach(function (d, i) { d.setAttribute('aria-pressed', i === ci ? 'true' : 'false'); });
				busy = false; turn = 0;
				warm(ci + 1);
			}, reduce ? 0 : 650);
		};
		var plan = function () {
			clearInterval(auto);
			if (reduce) return; /* máy bật "giảm chuyển động": không tự đổi xe, vẫn bấm chấm được */
			auto = setInterval(function () { if (visible && !document.hidden) go(ci + 1); }, 8000);
		};
		dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); plan(); }); });
		/* Vuốt ngang trên đầu trang để đổi xe (không cản cuộn dọc) */
		var sx = 0, sy = 0;
		hero.addEventListener('touchstart', function (e) { var t = e.touches[0]; sx = t.clientX; sy = t.clientY; }, { passive: true });
		hero.addEventListener('touchend', function (e) {
			var t = e.changedTouches[0], dx = t.clientX - sx, dy = t.clientY - sy;
			if (Math.abs(dx) > 56 && Math.abs(dx) > Math.abs(dy) * 1.8) { go(ci + (dx < 0 ? 1 : -1)); plan(); }
		}, { passive: true });
		warm(1);
		plan();
	}

	/* ---- Trang mẫu xe: canh cụm tấm ốp vào chính giữa khối (xe thiếu vài vị trí thì cụm không bị lệch một bên) ---- */
	if (!hero.classList.contains('hero2')) {
		var canh = function () {
			stage.style.translate = '';
			var s = stage.getBoundingClientRect(), l = 1e9, r = -1e9, t = 1e9, b = -1e9;
			all.forEach(function (p) {
				if (p.el.classList.contains('hp-off') || !p.img.getAttribute('src')) return;
				var c = p.img.getBoundingClientRect();
				if (!c.width || !c.height) return;
				l = Math.min(l, c.left); r = Math.max(r, c.right); t = Math.min(t, c.top); b = Math.max(b, c.bottom);
			});
			if (r < l) return;
			var dx = (s.left + s.right) / 2 - (l + r) / 2, dy = (s.top + s.bottom) / 2 - (t + b) / 2;
			stage.style.translate = dx.toFixed(1) + 'px ' + dy.toFixed(1) + 'px';
		};
		all.forEach(function (p) { p.img.addEventListener('load', canh); });
		window.addEventListener('resize', canh);
		window.addEventListener('load', canh);
		setTimeout(canh, 1600); /* sau hiệu ứng hiện dần của tấm ốp */
		canh();
	}

	/* ---- Chuyển động theo chuột và cuộn trang ---- */
	if ('IntersectionObserver' in window) {
		new IntersectionObserver(function (en) { visible = en[0].isIntersecting; if (visible) kick(); }).observe(hero);
	}
	if (reduce) { kick = function () {}; return; }
	var parts = slots.map(function (el) {
		return { el: el, d: parseFloat(el.getAttribute('data-d')) || .5, base: getComputedStyle(el).transform };
	});
	/* 0.22.0 — vệt bóng sáng trượt trên từng tấm ốp theo góc nghiêng (cắt theo đúng hình tấm ốp bằng mask) */
	var maskOk = window.CSS && CSS.supports && (CSS.supports('mask-image', 'url(a.png)') || CSS.supports('-webkit-mask-image', 'url(a.png)'));
	if (maskOk) {
		parts.forEach(function (p) {
			var inn = p.el.querySelector('.hp-in'), img = p.el.querySelector('img');
			if (!inn || !img) return;
			var gl = document.createElement('span');
			gl.className = 'hp-gl';
			gl.appendChild(document.createElement('i'));
			inn.appendChild(gl);
			p.gl = gl.firstChild;
			var setMask = function () {
				var u = img.currentSrc || img.src;
				if (!u) return;
				var v = 'url("' + u.replace(/"/g, '%22') + '")';
				gl.style.webkitMaskImage = v;
				gl.style.maskImage = v;
			};
			img.addEventListener('load', setMask);
			if (img.complete) setMask();
		});
	}
	var home = hero.classList.contains('hero2');
	var fine = window.matchMedia('(pointer: fine)').matches;
	var tx = 0, ty = 0, cx = 0, cy = 0, raf = 0;
	/* Điện thoại: không có chuột nên tấm ốp tự đung đưa nhẹ; máy có cảm biến nghiêng (Android) thì nghiêng theo tay.
	   iPhone đòi hỏi hộp xin quyền cảm biến nên không dùng, chỉ đung đưa. */
	var sway = !fine, gx = 0, gy = 0, gyro = false;
	var amp = sway ? 1.7 : 1;
	if (fine) {
		window.addEventListener('pointermove', function (e) {
			tx = e.clientX / window.innerWidth * 2 - 1;
			ty = e.clientY / window.innerHeight * 2 - 1;
			kick();
		}, { passive: true });
	} else if ('DeviceOrientationEvent' in window && typeof window.DeviceOrientationEvent.requestPermission !== 'function') {
		window.addEventListener('deviceorientation', function (e) {
			if (e.gamma === null || e.beta === null) return;
			gyro = true;
			gx = Math.max(-1, Math.min(1, e.gamma / 28));
			gy = Math.max(-1, Math.min(1, (e.beta - 50) / 28));
		}, { passive: true });
	}
	window.addEventListener('scroll', function () { kick(); }, { passive: true });
	window.addEventListener('resize', function () {
		parts.forEach(function (p) { p.el.style.transform = ''; p.base = getComputedStyle(p.el).transform; });
		kick();
	});
	function kick() { if (!raf && visible) raf = requestAnimationFrame(frame); }
	function frame(now) {
		raf = 0;
		if (sway) {
			var t = (now || performance.now()) / 1000;
			tx = Math.sin(t * .55) * (gyro ? .25 : .7) + gx * .9;
			ty = Math.cos(t * .4) * (gyro ? .15 : .4) + gy * .9;
		}
		cx += (tx - cx) * .08; cy += (ty - cy) * .08;
		var sy = home ? Math.min(window.scrollY, window.innerHeight) : 0; /* trang mẫu xe: khối nằm giữa trang, không trôi theo cuộn */
		parts.forEach(function (p) {
			var d = p.d, b = p.base === 'none' ? '' : p.base;
			p.el.style.transform = 'perspective(1000px) translate3d(' + (cx * d * 26).toFixed(2) + 'px,' +
				(cy * d * 16 - sy * d * .22).toFixed(2) + 'px,0) rotateY(' + (cx * d * 7 * amp).toFixed(2) + 'deg) rotateX(' +
				(-cy * d * 5 * amp).toFixed(2) + 'deg) ' + b;
			if (p.gl) p.gl.style.transform = 'translate3d(' + ((cx * (.55 + d * .45)) * 62).toFixed(2) + '%,0,0)';
		});
		if (sway) { if (!document.hidden) kick(); return; }
		if (Math.abs(tx - cx) > .001 || Math.abs(ty - cy) > .001) kick();
	}
	document.addEventListener('visibilitychange', function () { if (!document.hidden) kick(); });
	kick();
})();
