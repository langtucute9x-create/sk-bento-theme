<?php
/**
 * Trang Kích hoạt bảo hành điện tử: khách nhập mã giới thiệu trên hộp → nhận mã kích hoạt app (dữ liệu ở SK Core).
 */
get_header();
the_post();
$skb_hotline = skb_opt( 'hotline', '0868 055 555' );
$skb_ready   = skb_kh_ready();
?>
<div class="page-wrap">
	<article class="page-card kh">
		<h1><?php the_title(); ?></h1>
		<p class="tile-text kh-lead">Cảm ơn Quý khách đã chọn SK Ambient Light. Điền thông tin bên dưới để kích hoạt bảo hành điện tử 24 tháng và nhận mã kích hoạt ứng dụng.</p>
		<?php if ( trim( get_the_content() ) ) : ?><div class="entry kh-intro"><?php the_content(); ?></div><?php endif; ?>

		<?php if ( ! $skb_ready ) : ?>
			<div class="notice err" role="status">Hệ thống kích hoạt đang được nâng cấp. Vui lòng gọi hotline <a href="tel:<?php echo esc_attr( skb_tel( $skb_hotline ) ); ?>"><?php echo esc_html( $skb_hotline ); ?></a> để được kích hoạt.</div>
		<?php else : ?>
		<div class="kh-grid">
			<div class="kh-main">
				<form class="form kh-form" id="kh-act" novalidate>
					<input type="hidden" name="action" value="skb_kh_activate">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'skb_kh_activate' ) ); ?>">
					<div class="hp" aria-hidden="true"><label for="kh-website">Website</label><input id="kh-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
					<div><label for="kh-name">Họ và tên *</label><input id="kh-name" name="customer_name" type="text" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn An"></div>
					<div><label for="kh-phone">Số điện thoại *</label><input id="kh-phone" name="customer_phone" type="tel" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="0901 234 567"></div>
					<div><label for="kh-plate">Biển số xe *</label><input id="kh-plate" name="vehicle_plate" type="text" required maxlength="20" autocapitalize="characters" placeholder="30A-123.45"></div>
					<div><label for="kh-code">Mã giới thiệu trên hộp *</label><input id="kh-code" class="kh-code-input" name="referral_code" type="text" required maxlength="4" pattern="[A-Za-z]{4}" autocapitalize="characters" autocomplete="off" spellcheck="false" placeholder="KXPM"></div>
					<p class="kh-msg notice err full" data-msg hidden role="alert"></p>
					<div class="full"><button class="btn btn-light btn-lg" type="submit">Kích hoạt bảo hành</button></div>
				</form>

				<div class="kh-result" id="kh-result" hidden tabindex="-1">
					<p class="kh-ok">Kích hoạt thành công</p>
					<p class="tile-text">Mã kích hoạt ứng dụng của <span data-r="name"></span> · xe <span data-r="plate"></span></p>
					<div class="kh-codebox"><code data-r="code"></code><button type="button" class="btn btn-dark" data-copy>Sao chép mã</button></div>
					<dl class="mx-spec"><dt>Ngày kích hoạt</dt><dd data-r="activated"></dd><dt>Bảo hành đến</dt><dd data-r="until"></dd></dl>
					<h2>Hướng dẫn nhập mã vào ứng dụng</h2>
					<ol class="kh-steps">
						<li>Bấm <b>Sao chép mã</b> để lưu mã kích hoạt.</li>
						<li>Mở ứng dụng <b>SK Ambient Light</b> trên điện thoại.</li>
						<li>Chọn <b>Kết nối thiết bị</b>.</li>
						<li>Dán mã vào ô nhập mã và bấm <b>Xác nhận</b>.</li>
						<li>Khi ứng dụng báo <b>Kích hoạt thành công</b>, Quý khách dùng được đầy đủ tính năng.</li>
					</ol>
					<p class="tile-text">Lưu lại mã để dùng khi đổi hoặc cài lại điện thoại. Mỗi mã dùng tối đa 2 thiết bị; cần thêm vui lòng gọi hotline <?php echo esc_html( $skb_hotline ); ?>. Quên mã có thể tra lại bằng số điện thoại ở phần Tra cứu mã đã kích hoạt.</p>
				</div>
			</div>

			<aside class="kh-side">
				<h2>Tra cứu mã đã kích hoạt</h2>
				<p class="tile-text">Nhập số điện thoại đã dùng khi kích hoạt để xem lại mã.</p>
				<form class="form kh-form kh-look" id="kh-look" novalidate>
					<input type="hidden" name="action" value="skb_kh_lookup">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'skb_kh_lookup' ) ); ?>">
					<div class="hp" aria-hidden="true"><label for="kh-website2">Website</label><input id="kh-website2" type="text" name="website" tabindex="-1" autocomplete="off"></div>
					<div class="full"><label for="kh-lphone">Số điện thoại</label><input id="kh-lphone" name="lookup_phone" type="tel" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="0901 234 567"></div>
					<p class="kh-msg notice err full" data-msg hidden role="alert"></p>
					<div class="full"><button class="btn btn-dark" type="submit">Tra cứu</button></div>
				</form>
				<ul class="kh-list" id="kh-list" hidden></ul>
				<p class="tile-text kh-help">Cần hỗ trợ? Gọi <a href="tel:<?php echo esc_attr( skb_tel( $skb_hotline ) ); ?>"><?php echo esc_html( $skb_hotline ); ?></a>.</p>
			</aside>
		</div>
		<script>
		(function () {
			var url = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			function show(form, msg, field) {
				var m = form.querySelector('[data-msg]');
				form.querySelectorAll('[aria-invalid]').forEach(function (el) { el.removeAttribute('aria-invalid'); });
				m.textContent = msg || ''; m.hidden = !msg;
				if (field && form.elements[field]) { form.elements[field].setAttribute('aria-invalid', 'true'); form.elements[field].focus(); }
			}
			function send(form, done) {
				form.addEventListener('submit', function (e) {
					e.preventDefault();
					var btn = form.querySelector('button[type=submit]');
					if (btn.disabled) return;
					show(form, '');
					btn.disabled = true; var label = btn.textContent; btn.textContent = 'Đang gửi…';
					fetch(url, { method: 'POST', credentials: 'same-origin', body: new FormData(form) })
						.then(function (r) { return r.json(); })
						.then(function (j) {
							if (j && j.success) done(j.data);
							else show(form, (j && j.data && j.data.message) || 'Không gửi được, vui lòng thử lại.', j && j.data && j.data.field);
						})
						.catch(function () { show(form, 'Mất kết nối, vui lòng thử lại.'); })
						.finally(function () { btn.disabled = false; btn.textContent = label; });
				});
			}
			var act = document.getElementById('kh-act'), res = document.getElementById('kh-result');
			act.elements.referral_code.addEventListener('input', function () { this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '').slice(0, 4); });
			act.elements.vehicle_plate.addEventListener('blur', function () { this.value = this.value.toUpperCase(); });
			send(act, function (d) {
				res.querySelector('[data-r=code]').textContent = d.activation_code;
				res.querySelector('[data-r=name]').textContent = d.name;
				res.querySelector('[data-r=plate]').textContent = d.plate;
				res.querySelector('[data-r=activated]').textContent = d.activated;
				res.querySelector('[data-r=until]').textContent = d.warranty_until;
				act.hidden = true; res.hidden = false; res.focus(); res.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
			document.addEventListener('click', function (e) {
				var b = e.target.closest('[data-copy]'); if (!b) return;
				var code = (b.getAttribute('data-copy') || b.parentNode.querySelector('code').textContent).trim();
				(navigator.clipboard ? navigator.clipboard.writeText(code) : Promise.reject()).then(function () {
					var t = b.textContent; b.textContent = 'Đã sao chép'; setTimeout(function () { b.textContent = t; }, 1800);
				}).catch(function () { window.prompt('Sao chép mã:', code); });
			});
			var look = document.getElementById('kh-look'), list = document.getElementById('kh-list');
			send(look, function (d) {
				list.innerHTML = '';
				d.codes.forEach(function (c) {
					var li = document.createElement('li'), code = document.createElement('code'), meta = document.createElement('span'), btn = document.createElement('button');
					code.textContent = c.code;
					meta.textContent = 'Kích hoạt ' + c.activated + (c.warranty_until ? ' · bảo hành đến ' + c.warranty_until : '');
					btn.type = 'button'; btn.className = 'btn btn-dark'; btn.textContent = 'Sao chép'; btn.setAttribute('data-copy', c.code);
					li.appendChild(code); li.appendChild(meta); li.appendChild(btn); list.appendChild(li);
				});
				list.hidden = false;
			});
		})();
		</script>
		<?php endif; ?>
	</article>
</div>
<?php
get_footer();
