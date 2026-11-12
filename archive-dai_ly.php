<?php
/**
 * Danh sách đại lý: lọc theo tỉnh / thành, tìm theo tên, địa chỉ, SĐT
 */
get_header();
$q     = new WP_Query( array( 'post_type' => 'dai_ly', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
$tinhs = array();
foreach ( $q->posts as $p ) {
	$t = trim( (string) get_post_meta( $p->ID, '_skb_tinh', true ) );
	if ( '' !== $t ) {
		$tinhs[ $t ] = ( $tinhs[ $t ] ?? 0 ) + 1;
	}
}
uksort( $tinhs, function ( $a, $b ) {
	if ( 'Hà Nội' === $a ) { return -1; }
	if ( 'Hà Nội' === $b ) { return 1; }
	return strcoll( remove_accents( $a ), remove_accents( $b ) );
} );
?>
<div class="page-wrap">
	<section class="page-card dl">
		<h1>Đại lý lắp đặt toàn quốc</h1>
		<p class="tile-text" style="margin-bottom:20px"><?php echo esc_html( (string) $q->post_count ); ?> điểm lắp đặt tại <?php echo esc_html( (string) count( $tinhs ) ); ?> tỉnh, thành. Chọn khu vực hoặc gõ tên để tìm đại lý gần bạn.</p>
		<?php get_template_part( 'template-parts/dealer-map', null, array( 'filter' => true ) ); ?>
		<div class="dl-filter">
			<label class="dl-select"><span class="sr-only">Tỉnh / thành phố</span>
				<select id="dl-tinh">
					<option value="">Tất cả tỉnh / thành</option>
					<?php foreach ( $tinhs as $t => $n ) : ?>
						<option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( $t . ' (' . $n . ')' ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="dl-search"><span class="sr-only">Tìm đại lý</span>
				<input id="dl-q" type="search" placeholder="Tìm tên, địa chỉ, số điện thoại…" autocomplete="off">
			</label>
			<button type="button" class="btn btn-light dl-near" id="dl-near"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span>Đại lý gần tôi</span></button>
		</div>
		<p class="dl-near-msg tile-text" id="dl-near-msg" role="status" hidden></p>
		<div class="dealers dl-list">
			<?php
			while ( $q->have_posts() ) {
				$q->the_post();
				get_template_part( 'template-parts/dealer-card', null, array( 'img' => true ) );
			}
			wp_reset_postdata();
			?>
		</div>
		<p class="tile-text dl-empty" hidden>Chưa có đại lý khớp. Gọi hotline <a href="tel:<?php echo esc_attr( skb_tel( skb_opt( 'hotline', '0868 055 555' ) ) ); ?>"><?php echo esc_html( skb_opt( 'hotline', '0868 055 555' ) ); ?></a> để SK tư vấn điểm lắp gần nhất.</p>
		<?php
		$skb_tdl = get_page_by_path( 'tuyen-dai-ly' );
		if ( $skb_tdl && 'publish' === $skb_tdl->post_status ) :
			?>
			<p class="tile-text dl-tuyen">Bạn là gara muốn làm đại lý SK? <a href="<?php echo esc_url( get_permalink( $skb_tdl ) ); ?>">Xem trang tuyển đại lý</a></p>
		<?php endif; ?>
	</section>
</div>
<script>
(function () {
	var sel = document.getElementById('dl-tinh'), inp = document.getElementById('dl-q');
	var cards = Array.prototype.slice.call(document.querySelectorAll('.dl-list .dealer')), empty = document.querySelector('.dl-empty');
	var fold = function (s) { return s.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').toLowerCase(); };
	cards.forEach(function (c) { c._q = fold(c.getAttribute('data-q') || ''); });
	function apply() {
		var t = sel.value, q = fold(inp.value.trim()), n = 0;
		cards.forEach(function (c) {
			var ok = (!t || c.getAttribute('data-tinh') === t) && (!q || c._q.indexOf(q) !== -1);
			c.hidden = !ok; if (ok) n++;
		});
		empty.hidden = n > 0;
	}
	var map = document.querySelector('.vnmap-filter'), list = document.querySelector('.dl-filter');
	function mark() {
		if (!map) return;
		var on = false;
		map.querySelectorAll('.vn-pin, .vn-chip').forEach(function (p) { var m = p.getAttribute('data-tinh') === sel.value; p.classList.toggle('on', m); if (p.classList.contains('vn-chip')) p.setAttribute('aria-current', m ? 'true' : 'false'); on = on || m; });
		map.classList.toggle('has-on', on);
	}
	if (map) map.addEventListener('click', function (e) {
		var pin = e.target.closest('.vn-pin, .vn-chip'); if (!pin) return;
		e.preventDefault();
		var t = pin.getAttribute('data-tinh');
		sel.value = sel.value === t ? '' : t; apply(); mark();
		if (sel.value) list.scrollIntoView({ behavior: 'smooth', block: 'start' });
	});
	/* Đại lý gần tôi: lấy vị trí điện thoại, sắp xếp theo khoảng cách (đường chim bay) */
	var nearBtn = document.getElementById('dl-near'), nearMsg = document.getElementById('dl-near-msg'), listEl = document.querySelector('.dl-list');
	function say(t) { nearMsg.textContent = t; nearMsg.hidden = !t; }
	function km(a, b) {
		var R = 6371, r = Math.PI / 180, dLa = (b[0] - a[0]) * r, dLo = (b[1] - a[1]) * r;
		var h = Math.sin(dLa / 2) * Math.sin(dLa / 2) + Math.cos(a[0] * r) * Math.cos(b[0] * r) * Math.sin(dLo / 2) * Math.sin(dLo / 2);
		return 2 * R * Math.asin(Math.sqrt(h));
	}
	function near() {
		if (!navigator.geolocation) { say('Trình duyệt không hỗ trợ lấy vị trí. Hãy chọn tỉnh / thành ở ô bên cạnh.'); return; }
		nearBtn.disabled = true; say('Đang lấy vị trí của bạn…');
		if (window.skbEv) window.skbEv('gan_toi', '');
		navigator.geolocation.getCurrentPosition(function (pos) {
			nearBtn.disabled = false;
			var me = [pos.coords.latitude, pos.coords.longitude];
			sel.value = ''; inp.value = ''; apply(); mark();
			var withLL = cards.filter(function (c) { return c.dataset.ll; });
			withLL.forEach(function (c) {
				var p = c.dataset.ll.split(',').map(Number), d = km(me, p);
				c._d = d;
				var el = c.querySelector('.dl-km');
				el.textContent = (c.dataset.xap ? '~' : '') + (d < 10 ? d.toFixed(1).replace('.', ',') : Math.round(d)) + ' km' + (c.dataset.xap ? ' (ước theo tỉnh)' : '');
				el.hidden = false;
			});
			cards.slice().sort(function (a, b) { return (a._d == null ? 1e9 : a._d) - (b._d == null ? 1e9 : b._d); }).forEach(function (c) { listEl.appendChild(c); });
			var first = cards.filter(function (c) { return c._d != null; }).sort(function (a, b) { return a._d - b._d; })[0];
			say(first ? 'Đã xếp đại lý từ gần đến xa. Gần nhất: ' + first.querySelector('.name').textContent + ' (' + first.querySelector('.dl-km').textContent + ').' : 'Chưa có toạ độ đại lý.');
			listEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}, function (err) {
			nearBtn.disabled = false;
			say(err && err.code === 1 ? 'Bạn chưa cho phép lấy vị trí. Bật quyền vị trí cho trình duyệt hoặc chọn tỉnh / thành ở ô bên cạnh.' : 'Chưa lấy được vị trí. Thử lại hoặc chọn tỉnh / thành.');
		}, { enableHighAccuracy: false, timeout: 12000, maximumAge: 600000 });
	}
	nearBtn.addEventListener('click', near);
	if (new URLSearchParams(location.search).get('gan') === '1') near();
	var qt = new URLSearchParams(location.search).get('tinh');
	if (qt && Array.prototype.some.call(sel.options, function (o) { return o.value === qt; })) { sel.value = qt; apply(); }
	mark();
	sel.addEventListener('change', function () { apply(); mark(); }); inp.addEventListener('input', apply);
})();
</script>
<?php
get_footer();
