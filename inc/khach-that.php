<?php
/**
 * Ảnh và video khách thật theo dòng xe.
 *  - Video: ô "Link video" ở trang sửa mẫu xe (YouTube, YouTube Shorts, TikTok, Facebook — mỗi dòng một link);
 *    để trống thì tự lấy video YouTube có sẵn trong mô tả (chép từ web cũ).
 *  - Ảnh khách đã lắp: chọn từ Thư viện (meta _skb_anh_khach).
 * Video YouTube hiện dạng ảnh bìa, bấm mới tải trình phát (trang nhẹ hơn nhiều so với nhúng thẳng).
 */
defined( 'ABSPATH' ) || exit;

/** Mã video YouTube từ link / mã nhúng, hoặc '' */
function skb_yt_id( $url ) {
	if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', (string) $url, $m ) ) {
		return $m[1];
	}
	return '';
}

/** Danh sách video của mẫu xe: [ ['yt' => id] | ['url' => link, 'nen' => 'TikTok'] ] */
function skb_khach_video( $post_id ) {
	$out  = array();
	$seen = array();
	$raw  = trim( (string) get_post_meta( $post_id, '_skb_video', true ) );
	$from = '' !== $raw ? preg_split( '/\s+/', $raw ) : array();
	if ( ! $from ) {
		preg_match_all( '~<iframe[^>]+src=["\']([^"\']+)["\']~i', (string) get_post_field( 'post_content', $post_id ), $m );
		$from = $m[1];
	}
	foreach ( $from as $u ) {
		$u  = trim( $u );
		$yt = skb_yt_id( $u );
		if ( $yt ) {
			if ( ! isset( $seen[ $yt ] ) ) {
				$seen[ $yt ] = 1;
				$out[]       = array( 'yt' => $yt );
			}
		} elseif ( '' !== $raw && preg_match( '~^https?://~', $u ) ) { // link khác YouTube chỉ lấy từ ô nhập tay
			$host  = wp_parse_url( $u, PHP_URL_HOST );
			$nen   = false !== stripos( (string) $host, 'tiktok' ) ? 'TikTok' : ( false !== stripos( (string) $host, 'facebook' ) || false !== stripos( (string) $host, 'fb.watch' ) ? 'Facebook' : 'Video' );
			$out[] = array( 'url' => esc_url_raw( $u ), 'nen' => $nen );
		}
	}
	return $out;
}

/** Ảnh khách đã lắp (ID ảnh) */
function skb_khach_anh( $post_id ) {
	return array_values( array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $post_id, '_skb_anh_khach', true ) ) ) ) );
}

/** Ô video YouTube nhẹ: ảnh bìa + nút phát */
function skb_yt_facade( $id, $title = '' ) {
	return sprintf(
		'<div class="yt" data-yt="%1$s"><button type="button" class="yt-play" aria-label="%2$s"><img src="https://i.ytimg.com/vi/%1$s/hqdefault.jpg" alt="" loading="lazy" decoding="async" width="480" height="360"><span class="yt-btn" aria-hidden="true"></span></button></div>',
		esc_attr( $id ),
		esc_attr( $title ? skb__( 'Phát video' ) . ': ' . $title : skb__( 'Phát video' ) )
	);
}

/* Mô tả mẫu xe / bài viết: video YouTube nhúng → ảnh bìa bấm mới phát. Ở mẫu xe, video đã có ở khối "Video thực tế" thì bỏ khỏi mô tả. */
add_filter( 'the_content', function ( $html ) {
	if ( is_admin() || ! is_singular() || false === stripos( $html, '<iframe' ) ) {
		return $html;
	}
	$top = array();
	if ( is_singular( 'mau_xe' ) ) {
		foreach ( skb_khach_video( get_the_ID() ) as $v ) {
			if ( isset( $v['yt'] ) ) {
				$top[ $v['yt'] ] = 1;
			}
		}
	}
	// bảng chỉ để xếp video (kiểu web cũ) mà mọi video đều đã lên khối trên → bỏ cả bảng
	if ( $top ) {
		$html = preg_replace_callback( '~<table\b[^>]*>.*?</table>~is', function ( $m ) use ( $top ) {
			if ( ! preg_match_all( '~<iframe[^>]+src=["\']([^"\']+)["\'][^>]*>\s*</iframe>~i', $m[0], $f ) ) {
				return $m[0];
			}
			foreach ( $f[1] as $src ) {
				if ( ! isset( $top[ skb_yt_id( $src ) ] ) ) {
					return $m[0];
				}
			}
			$rest = preg_replace( '~<iframe.*?</iframe>~is', '', $m[0] );
			return ( '' === trim( html_entity_decode( wp_strip_all_tags( $rest ) ), " \t\n\r\0\x0B\xC2\xA0" ) && false === stripos( $rest, '<img' ) ) ? '' : $m[0];
		}, $html );
	}
	return preg_replace_callback( '~<iframe\b[^>]*src=["\']([^"\']+)["\'][^>]*>\s*</iframe>~i', function ( $m ) use ( $top ) {
		$id = skb_yt_id( $m[1] );
		if ( ! $id ) {
			return $m[0];
		}
		return isset( $top[ $id ] ) ? '' : skb_yt_facade( $id );
	}, $html );
}, 20 );

/* ---------- Trang sửa mẫu xe: khung "Ảnh và video khách thật" ---------- */
add_action( 'add_meta_boxes_mau_xe', function () {
	add_meta_box( 'skb_khach_box', 'Ảnh và video khách thật', 'skb_khach_box', 'mau_xe', 'normal', 'default' );
} );

function skb_khach_box( $post ) {
	wp_nonce_field( 'skb_khach_save', 'skb_khach_nonce' );
	$raw  = (string) get_post_meta( $post->ID, '_skb_video', true );
	$auto = '' === trim( $raw ) ? skb_khach_video( $post->ID ) : array();
	$ids  = skb_khach_anh( $post->ID );
	?>
	<p><label for="skb-video"><strong>Link video</strong> — mỗi dòng một link YouTube, YouTube Shorts, TikTok hoặc Facebook. Hiện thành khối "Video thực tế" ngay dưới phần giá.</label></p>
	<textarea id="skb-video" name="skb_video" rows="4" class="widefat" placeholder="https://youtube.com/shorts/…&#10;https://www.tiktok.com/@sklight/video/…"><?php echo esc_textarea( $raw ); ?></textarea>
	<?php if ( $auto ) : ?>
		<p class="description">Đang để trống nên web tự lấy <?php echo count( $auto ); ?> video YouTube có trong mô tả. Điền vào ô trên nếu muốn chọn video khác hoặc đổi thứ tự.</p>
	<?php endif; ?>
	<p style="margin-top:14px"><strong>Ảnh khách đã lắp</strong> — ảnh xe thật sau khi lắp (nên xin phép khách, che biển số).</p>
	<div id="skb-khach-anh" style="display:flex;flex-wrap:wrap;gap:6px">
		<?php foreach ( $ids as $id ) : $u = wp_get_attachment_image_url( $id, 'thumbnail' ); if ( ! $u ) { continue; } ?>
			<span data-id="<?php echo (int) $id; ?>" style="position:relative;width:72px;height:72px;border-radius:6px;background:#eee center/cover url(<?php echo esc_url( $u ); ?>)"><button type="button" class="button-link" style="position:absolute;top:0;right:2px;color:#b32d2e;font-weight:700" aria-label="Bỏ ảnh">×</button></span>
		<?php endforeach; ?>
	</div>
	<input type="hidden" name="skb_anh_khach" id="skb-anh-khach" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
	<p><button type="button" class="button" id="skb-khach-them">Thêm ảnh khách</button></p>
	<script>
	(function () {
		var box = document.getElementById('skb-khach-anh'), out = document.getElementById('skb-anh-khach');
		function sync() { out.value = Array.prototype.map.call(box.children, function (s) { return s.dataset.id; }).join(','); }
		box.addEventListener('click', function (e) { if (e.target.closest('button')) { e.target.closest('span').remove(); sync(); } });
		document.getElementById('skb-khach-them').addEventListener('click', function () {
			var f = wp.media({ title: 'Ảnh khách đã lắp', multiple: true, library: { type: 'image' } });
			f.on('select', function () {
				f.state().get('selection').each(function (m) {
					var a = m.toJSON(), s = document.createElement('span');
					s.dataset.id = a.id;
					s.style.cssText = 'position:relative;width:72px;height:72px;border-radius:6px;background:#eee center/cover url(' + ((a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url) + ')';
					s.innerHTML = '<button type="button" class="button-link" style="position:absolute;top:0;right:2px;color:#b32d2e;font-weight:700" aria-label="Bỏ ảnh">×</button>';
					box.appendChild(s);
				});
				sync();
			});
			f.open();
		});
	})();
	</script>
	<?php
}

add_action( 'save_post_mau_xe', function ( $post_id ) {
	if ( ! isset( $_POST['skb_khach_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skb_khach_nonce'] ) ), 'skb_khach_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$lines = preg_split( '/\s+/', (string) wp_unslash( $_POST['skb_video'] ?? '' ) ); // phpcs:ignore
	$keep  = array();
	foreach ( $lines as $l ) {
		$l = esc_url_raw( trim( $l ) );
		if ( $l && preg_match( '~^https?://~', $l ) ) {
			$keep[] = $l;
		}
	}
	update_post_meta( $post_id, '_skb_video', implode( "\n", array_slice( array_unique( $keep ), 0, 20 ) ) );
	$ids = array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $_POST['skb_anh_khach'] ?? '' ) ) ) ); // phpcs:ignore
	update_post_meta( $post_id, '_skb_anh_khach', implode( ',', array_slice( array_unique( $ids ), 0, 60 ) ) );
	delete_transient( 'skb_video_trang_chu' );
}, 20 );

/** Khối "Video thực tế" + "Ảnh khách đã lắp" trên trang mẫu xe */
function skb_khach_that_khoi( $post_id ) {
	$videos = skb_khach_video( $post_id );
	$anh    = skb_khach_anh( $post_id );
	if ( ! $videos && ! $anh ) {
		return;
	}
	$ten = function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai() ? skb_ten_xe( $post_id ) : ( function_exists( 'skb_ten_ngan' ) ? skb_ten_ngan( get_the_title( $post_id ) ) : get_the_title( $post_id ) );
	?>
	<section class="khach" aria-label="<?php echo esc_attr( skb__( 'Video và ảnh khách thật' ) ); ?>">
		<?php if ( $videos ) : ?>
			<h2 class="khach-h"><?php skb_e( 'Video thực tế' ); ?> <span><?php echo esc_html( $ten ); ?></span></h2>
			<div class="khach-rail">
				<?php foreach ( array_slice( $videos, 0, 8 ) as $v ) : ?>
					<?php if ( isset( $v['yt'] ) ) : ?>
						<?php echo skb_yt_facade( $v['yt'], $ten ); // phpcs:ignore ?>
					<?php else : ?>
						<a class="vlink" href="<?php echo esc_url( $v['url'] ); ?>" target="_blank" rel="noopener"><span class="vlink-n"><?php echo esc_html( $v['nen'] ); ?></span><span><?php echo esc_html( sprintf( skb__( 'Xem video trên %s' ), $v['nen'] ) ); ?> ↗</span></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( $anh ) : ?>
			<h2 class="khach-h"><?php skb_e( 'Xe khách đã lắp' ); ?> <span><?php echo esc_html( sprintf( skb__( '%d ảnh' ), count( $anh ) ) ); ?></span></h2>
			<div class="khach-anh">
				<?php foreach ( $anh as $i => $id ) : $big = wp_get_attachment_image_src( $id, 'large' ); if ( ! $big ) { continue; } ?>
					<a href="<?php echo esc_url( $big[0] ); ?>" data-lb="khach" aria-label="<?php echo esc_attr( skb__( 'Xem ảnh lớn' ) ); ?> <?php echo (int) $i + 1; ?>"><?php echo wp_get_attachment_image( $id, 'medium', false, array( 'loading' => 'lazy', 'alt' => skb__( 'Xe khách đã lắp' ) . ' ' . $ten ) ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

/** Video cho trang chủ: mỗi mẫu xe lấy 1 video YouTube, tối đa 8 (lưu tạm 1 ngày) */
function skb_video_trang_chu() {
	$c = get_transient( 'skb_video_trang_chu2' );
	if ( is_array( $c ) ) {
		return $c;
	}
	// lưu đường dẫn tiếng Việt (dùng chung mọi ngôn ngữ; bản nước ngoài đổi đường dẫn khi in ra)
	return function_exists( 'skb_ngu_tat' ) ? skb_ngu_tat( 'skb_video_trang_chu_dung' ) : skb_video_trang_chu_dung();
}
function skb_video_trang_chu_dung() {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'mau_xe', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order date', 'order' => 'ASC' ) ) as $p ) {
		foreach ( skb_khach_video( $p->ID ) as $v ) {
			if ( isset( $v['yt'] ) ) {
				$out[] = array( 'yt' => $v['yt'], 't' => function_exists( 'skb_ten_ngan' ) ? skb_ten_ngan( $p->post_title ) : $p->post_title, 'u' => get_permalink( $p ), 'id' => (int) $p->ID );
				break;
			}
		}
		if ( count( $out ) >= 8 ) {
			break;
		}
	}
	set_transient( 'skb_video_trang_chu2', $out, DAY_IN_SECONDS );
	return $out;
}
add_action( 'save_post_mau_xe', function () {
	delete_transient( 'skb_video_trang_chu' );
	delete_transient( 'skb_video_trang_chu2' );
} );
