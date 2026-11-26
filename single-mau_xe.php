<?php
/**
 * Một mẫu xe: ảnh, giá, mô tả, nút đặt lịch
 */
get_header();
while ( have_posts() ) :
	the_post();
	$brands  = get_the_terms( get_the_ID(), 'hang_xe' );
	$gia     = skb_gia();
	$gallery = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( get_the_ID(), '_skb_gallery', true ) ) ) );
	$hotline = skb_opt( 'hotline', '0868 055 555' );
	$goi     = skb_goi();
	$chon    = isset( $_GET['goi'] ) ? absint( $_GET['goi'] ) - 1 : -1; // phpcs:ignore
	if ( $chon >= count( $goi ) ) {
		$chon = -1;
	}
	$book    = home_url( '/dat-lich/' );
	$book_xe = get_the_title() . ( $chon >= 0 ? ' — ' . $goi[ $chon ]['ten'] : '' );
	$ngoai   = skb_ngu_ngoai();
	$tien    = $ngoai ? 'skb_ngu_tien' : 'skb_tien';
	// giá hiển thị từng gói: bản Việt theo chương trình khuyến mại (ngoài chương trình chỉ giá niêm yết), bản nước ngoài như cũ
	$km      = ! $ngoai && skb_km_dang_chay();
	$gp      = array_map( function ( $g ) {
		return skb_km_gia( $g['gia'], $g['goc'] );
	}, $goi );
	$khoang_f = function ( $v ) use ( $tien ) {
		$v = array_filter( $v );
		return $v ? ( min( $v ) === max( $v ) ? $tien( min( $v ) ) : $tien( min( $v ) ) . ' – ' . $tien( max( $v ) ) ) : skb__( 'Liên hệ' );
	};
	$khoang  = $khoang_f( wp_list_pluck( $gp, 'ban' ) );
	$khoang_niem = $khoang_f( wp_list_pluck( $gp, 'niem' ) );
	$ten_goi = function ( $t ) use ( $ngoai ) {
		return $ngoai ? skb_ngu_ten_goi( $t ) : $t;
	};
	$wa_xe   = $ngoai ? skb_ten_xe() . ( $chon >= 0 ? ' — ' . skb_ngu_ten_goi( $goi[ $chon ]['ten'] ) : '' ) : '';
	if ( has_post_thumbnail() ) {
		array_unshift( $gallery, get_post_thumbnail_id() );
	}
	$top = null;
	if ( $brands && ! is_wp_error( $brands ) ) {
		foreach ( $brands as $b ) {
			if ( ! $b->parent ) { $top = $b; break; }
		}
		if ( ! $top ) { $top = $brands[0]; }
	}
	?>
	<div class="page-wrap">
		<article class="page-card mx">
			<?php if ( $top ) : ?>
				<p class="tile-text"><a href="<?php echo esc_url( get_term_link( $top ) ); ?>">← <?php echo esc_html( skb__( $top->name ) ); ?></a></p>
			<?php endif; ?>
			<div class="mx-top">
				<div class="mx-media">
					<?php if ( $gallery ) : ?>
						<div class="mx-main"><?php echo wp_get_attachment_image( $gallery[0], 'large', false, array( 'id' => 'mx-main', 'fetchpriority' => 'high' ) ); ?></div>
						<?php if ( count( $gallery ) > 1 ) : ?>
							<div class="mx-thumbs" role="list">
								<?php foreach ( $gallery as $i => $aid ) : $full = wp_get_attachment_image_src( $aid, 'large' ); if ( ! $full ) { continue; } ?>
									<button type="button" class="mx-th<?php echo 0 === $i ? ' on' : ''; ?>" data-src="<?php echo esc_url( $full[0] ); ?>" data-srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $aid, 'large' ) ); ?>" aria-label="<?php echo esc_attr( skb__( 'Ảnh' ) ); ?> <?php echo (int) $i + 1; ?>"><?php echo wp_get_attachment_image( $aid, 'thumbnail', false, array( 'loading' => 'lazy' ) ); ?></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<div class="mx-info">
					<h1><?php echo $ngoai ? esc_html( skb_ngu_tieu_de_xe() ) : esc_html( get_the_title() ); ?></h1>
					<?php if ( function_exists( 'skb_catalog_dau' ) ) { skb_catalog_dau( get_the_ID() ); } ?>
					<?php if ( $goi ) : $g0 = $chon >= 0 ? $gp[ $chon ] : null; $p0 = $g0 ? ( $g0['ban'] ? $tien( $g0['ban'] ) : skb__( 'Liên hệ' ) ) : $khoang; ?>
						<p class="mx-price" id="mx-price" aria-live="polite"><strong data-p<?php echo $km ? skb_km_niem_attr( $p0, $g0 ? ( $g0['niem'] ? $tien( $g0['niem'] ) : skb__( 'Liên hệ' ) ) : $khoang_niem ) : ''; // phpcs:ignore ?>><?php echo esc_html( $p0 ); ?></strong> <s data-o data-km-an<?php echo ( $g0 && $g0['goc'] ) ? '' : ' hidden'; ?>><?php echo esc_html( $g0 && $g0['goc'] ? $tien( $g0['goc'] ) : '' ); ?></s> <span><?php echo esc_html( $ngoai ? skb__( 'Giá bán tại Việt Nam, gồm VAT' ) : 'đã gồm VAT' ); ?></span></p>
						<fieldset class="mx-goi">
							<legend><?php skb_e( 'Chọn gói' ); ?> <span><?php echo esc_html( sprintf( skb__( '%d lựa chọn' ), count( $goi ) ) ); ?></span></legend>
							<div class="goi-list">
								<?php
								foreach ( $goi as $i => $g ) :
									$big = $g['anh'] ? wp_get_attachment_image_src( $g['anh'], 'large' ) : false;
									$p   = $gp[ $i ];
									$off = $p['off'] ? '-' . $p['off'] . '%' : '';
									$p_t = $p['ban'] ? $tien( $p['ban'] ) : skb__( 'Liên hệ' );
									$p_n = $p['niem'] ? $tien( $p['niem'] ) : skb__( 'Liên hệ' );
									?>
									<label class="goi">
										<input type="radio" name="skb_goi" value="<?php echo (int) $i + 1; ?>"<?php checked( $i, $chon ); ?> data-ten="<?php echo esc_attr( $ten_goi( $g['ten'] ) ); ?>" data-gia="<?php echo esc_attr( $p_t ); ?>" data-goc="<?php echo esc_attr( $p['goc'] ? $tien( $p['goc'] ) : '' ); ?>"<?php if ( $km && $p_n !== $p_t ) : ?> data-niem="<?php echo esc_attr( $p_n ); ?>"<?php endif; ?><?php if ( $big ) : ?> data-img="<?php echo esc_url( $big[0] ); ?>" data-srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $g['anh'], 'large' ) ); ?>"<?php endif; ?>>
										<span class="goi-ten"><?php echo esc_html( $ten_goi( $g['ten'] ) ); ?></span>
										<span class="goi-gia"><span<?php echo $km ? skb_km_niem_attr( $p_t, $p_n ) : ''; // phpcs:ignore ?>><?php echo esc_html( $p_t ); ?></span><?php if ( $off ) : ?> <em class="goi-off" data-km-an><?php echo esc_html( $off ); ?></em><?php endif; ?></span>
										<?php if ( $km && $p['goc'] ) : ?><s class="goi-goc" data-km-an><?php echo esc_html( $tien( $p['goc'] ) ); ?></s><?php endif; ?>
									</label>
								<?php endforeach; ?>
							</div>
							<?php foreach ( $ngoai ? array() : $goi as $i => $g ) : $muc = skb_goi_muc( $g['mo_ta'] ); ?>
								<div class="goi-ct" data-ct="<?php echo (int) $i + 1; ?>"<?php echo $i === $chon ? '' : ' hidden'; ?>>
									<p class="goi-ct-h"><?php echo esc_html( $g['ten'] ); ?><?php echo $muc ? ' gồm:' : ''; ?></p>
									<?php if ( count( $muc ) > 1 ) : ?>
										<ul><?php foreach ( $muc as $m ) { echo '<li>' . esc_html( $m ) . '</li>'; } ?></ul>
									<?php elseif ( $muc ) : ?>
										<p><?php echo esc_html( $muc[0] ); ?></p>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
							<p class="goi-ct goi-ct-chua" data-ct="0"<?php echo $chon >= 0 ? ' hidden' : ''; ?>><?php echo esc_html( $ngoai ? skb__( 'Chọn một gói để xem giá và ảnh.' ) : 'Chọn một gói để xem chi tiết các món đi kèm, giá và ảnh.' ); ?></p>
						</fieldset>
						<?php if ( $km && function_exists( 'skb_km_hop_qua' ) ) { skb_km_hop_qua( get_the_ID(), $goi, $gp ); } ?>
					<?php elseif ( $gia ) : ?>
						<p class="mx-price"><?php skb_e( 'Giá từ' ); ?> <strong><?php echo esc_html( $ngoai ? skb_ngu_tien( (float) get_post_meta( get_the_ID(), '_skb_gia', true ) ) : $gia ); ?></strong> <span><?php echo esc_html( $ngoai ? skb__( 'Giá bán tại Việt Nam, gồm VAT' ) : 'đã gồm VAT' ); ?></span></p>
					<?php else : ?>
						<p class="mx-price"><strong><?php skb_e( 'Liên hệ để báo giá' ); ?></strong></p>
					<?php endif; ?>
					<?php if ( $ngoai ) : ?>
						<div class="mx-sum"><p><?php echo esc_html( skb_ngu_dien( skb__( '@xe_tt_1' ) ) ); ?></p><p><?php echo esc_html( skb_ngu_dien( skb__( '@xe_tt_2' ) ) ); ?></p></div>
						<dl class="mx-spec">
							<dt><?php skb_e( 'Lắp cho' ); ?></dt><dd><?php echo esc_html( skb_ten_xe() ); ?></dd>
							<dt><?php skb_e( 'Bảo hành' ); ?></dt><dd><?php skb_e( '24 tháng' ); ?></dd>
							<dt><?php skb_e( 'Tình trạng' ); ?></dt><dd><?php skb_e( 'Mới 100%, đủ phụ kiện' ); ?></dd>
							<dt><?php skb_e( 'Điều khiển' ); ?></dt><dd><?php skb_e( 'App điện thoại và nút bấm' ); ?></dd>
							<dt><?php skb_e( 'Lắp đặt' ); ?></dt><dd><?php skb_e( 'Cắm giắc chờ, không cắt dây zin' ); ?></dd>
						</dl>
						<div class="mx-cta">
							<a class="btn btn-light btn-lg btn-wa" data-wa href="<?php echo esc_url( skb_wa_url( $wa_xe ) ); ?>" target="_blank" rel="noopener"><?php echo skb_wa_icon( 18 ); // phpcs:ignore ?> <?php skb_e( 'Nhắn WhatsApp về mẫu này' ); ?></a>
							<a class="btn btn-ghost btn-lg" href="tel:+<?php echo esc_attr( skb_wa_so() ); ?>"><?php skb_e( 'Gọi' ); ?> <?php echo esc_html( skb_tel_quoc_te() ); ?></a>
						</div>
						<ul class="mx-points">
							<li><?php skb_e( 'Ốp thay zin theo đúng đời xe, không cắt dây zin' ); ?></li>
							<li><?php skb_e( 'Điều khiển màu, hiệu ứng qua app và nút bấm' ); ?></li>
							<li><?php skb_e( 'Nhắn WhatsApp để được báo giá và vận chuyển tới nước bạn' ); ?></li>
						</ul>
					<?php else : ?>
					<?php $tt = skb_tom_tat(); ?>
					<?php if ( $tt['doan'] ) : ?><div class="mx-sum"><?php foreach ( $tt['doan'] as $d ) { echo '<p>' . esc_html( $d ) . '</p>'; } ?></div><?php endif; ?>
					<?php if ( $tt['muc'] ) : ?><dl class="mx-spec"><?php foreach ( $tt['muc'] as $m ) { echo '<dt>' . esc_html( $m[0] ) . '</dt><dd>' . esc_html( $m[1] ) . '</dd>'; } ?></dl><?php endif; ?>
					<div class="mx-cta">
						<a class="btn btn-light btn-lg" data-book href="<?php echo esc_url( add_query_arg( 'xe', rawurlencode( $book_xe ), $book ) ); ?>"><?php echo $chon >= 0 ? 'Đặt lịch lắp gói này' : 'Đặt lịch lắp mẫu này'; ?></a>
						<a class="btn btn-ghost btn-lg" href="tel:<?php echo esc_attr( skb_tel( $hotline ) ); ?>">Gọi <?php echo esc_html( $hotline ); ?></a>
					</div>
					<ul class="mx-points">
						<li>Ốp thay zin theo đúng đời xe, không cắt dây zin</li>
						<li>Điều khiển màu, hiệu ứng qua app và giọng nói</li>
						<li>Lắp đặt tại hệ thống đại lý SK toàn quốc</li>
					</ul>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( function_exists( 'skb_op_khoi_mau_xe' ) ) { skb_op_khoi_mau_xe( get_the_ID() ); } ?>
			<?php skb_khach_that_khoi( get_the_ID() ); ?>
			<?php if ( ! $ngoai ) : ?><div class="entry mx-desc"><?php the_content(); ?></div><?php endif; ?>
		</article>
	</div>
	<script>
	(function () {
		var radios = document.querySelectorAll('.mx-goi input'); if (!radios.length) return;
		var price = document.getElementById('mx-price'), title = <?php echo wp_json_encode( $ngoai ? skb_ten_xe() : get_the_title() ); ?>, book = <?php echo wp_json_encode( $book ); ?>;
		var wa = <?php echo wp_json_encode( $ngoai ? array( 'so' => skb_wa_so(), 'cau' => skb__( 'Xin chào SK Ambient Light, tôi quan tâm: %s' ) ) : null ); ?>;
		radios.forEach(function (r) {
			r.addEventListener('change', function () {
				if (!r.checked) return;
				price.querySelector('[data-p]').textContent = r.dataset.gia;
				var o = price.querySelector('[data-o]'); o.textContent = r.dataset.goc; o.hidden = !r.dataset.goc;
				var href = book + '?xe=' + encodeURIComponent(title + ' — ' + r.dataset.ten);
				if (wa) document.querySelectorAll('[data-wa]').forEach(function (a) { a.href = 'https://wa.me/' + wa.so + '?text=' + encodeURIComponent(wa.cau.replace('%s', title + ' — ' + r.dataset.ten)); });
				document.querySelectorAll('[data-book]').forEach(function (a) { a.href = href; var s = a.querySelector('span') || a; if (/mẫu này/.test(s.textContent)) s.textContent = s.textContent.replace('mẫu này', 'gói này'); });
				document.querySelectorAll('.goi-ct').forEach(function (c) { c.hidden = c.dataset.ct !== r.value; });
				var bar = document.querySelector('[data-gia-bar]'); if (bar) bar.textContent = r.dataset.ten + ' · ' + r.dataset.gia;
				var m = document.getElementById('mx-main');
				if (m && r.dataset.img) {
					m.src = r.dataset.img; if (r.dataset.srcset) m.srcset = r.dataset.srcset; else m.removeAttribute('srcset');
					document.querySelectorAll('.mx-th').forEach(function (t) { t.classList.remove('on'); });
				}
				try { var u = new URL(location.href); u.searchParams.set('goi', r.value); history.replaceState(null, '', u); } catch (e) {}
			});
		});
	})();
	document.querySelectorAll('.mx-th').forEach(function (b) {
		b.addEventListener('click', function () {
			var m = document.getElementById('mx-main'); if (!m) return;
			m.src = b.dataset.src; if (b.dataset.srcset) m.srcset = b.dataset.srcset; else m.removeAttribute('srcset');
			document.querySelectorAll('.mx-th').forEach(function (o) { o.classList.toggle('on', o === b); });
		});
	});
	</script>
	<?php
endwhile;
get_footer();
