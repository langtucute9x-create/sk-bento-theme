<?php
/**
 * Tối ưu ảnh trong nội dung (Công cụ › Tối ưu ảnh nội dung).
 *
 * Ảnh trong mô tả mẫu xe / bài viết chép thẳng từ web cũ là file gốc (0,5–5 MB), không nằm trong Thư viện
 * nên WordPress không có bản nhỏ. Mỗi ảnh được:
 *   1. đưa vào Thư viện (một file chỉ đưa một lần, đánh dấu meta _skb_anh_nguon = đường dẫn con trong uploads);
 *   2. thu về tối đa 2048px và tạo bản WebP 300 / 768 / 1024 / 1536px (GD của máy chủ);
 *   3. đổi thẻ <img> trong nội dung sang ảnh Thư viện (class wp-image-ID, width/height của bản 1024px)
 *      để WordPress tự thêm srcset/sizes, loading="lazy", decoding="async" khi hiển thị.
 * Nội dung cũ của mỗi bài lưu ở meta _skb_nd_truoc_anh (lần đầu) để hoàn tác. File gốc không bị xoá.
 * Ghi thẳng post_content (không qua wp_update_post) để bộ lọc HTML không cắt iframe/style của nội dung cũ.
 */

defined( 'ABSPATH' ) || exit;

function skb_anh_loai() {
	return array( 'mau_xe', 'post', 'page' );
}

add_action( 'admin_menu', function () {
	add_management_page( 'Tối ưu ảnh nội dung', 'Tối ưu ảnh nội dung', 'manage_options', 'skb-toi-uu-anh', 'skb_anh_page' );
} );

function skb_anh_guard() {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'skb_anh', '_n', false ) ) {
		wp_send_json_error( 'Không có quyền hoặc phiên đã hết hạn.' );
	}
	@set_time_limit( 60 ); // phpcs:ignore
}

/** Đường dẫn con trong uploads của một link ảnh, null nếu không phải ảnh của web này. */
function skb_anh_rel( $src, $base ) {
	$bo  = function ( $u ) {
		return preg_replace( '#^(https?:)?//(www\.)?#i', '', (string) $u );
	};
	$s   = $bo( html_entity_decode( $src ) );
	$b   = $bo( $base );
	if ( 0 !== strpos( $s, $b ) ) {
		return null;
	}
	$rel = rawurldecode( (string) strtok( substr( $s, strlen( $b ) ), '?#' ) );
	if ( '' === $rel || false !== strpos( $rel, '..' ) || ! preg_match( '/\.(jpe?g|png|webp)$/i', $rel ) ) {
		return null;
	}
	return $rel;
}

/** Ảnh Thư viện $id có phải là file $rel (bản chính, bản gốc hoặc một cỡ nhỏ) không. */
function skb_anh_la_cua( $id, $rel ) {
	if ( 'attachment' !== get_post_type( $id ) ) {
		return false;
	}
	$meta = wp_get_attachment_metadata( $id );
	if ( empty( $meta['file'] ) ) {
		return false;
	}
	$dir   = dirname( $meta['file'] );
	$dir   = '.' === $dir ? '' : $dir . '/';
	$files = array( $meta['file'] );
	if ( ! empty( $meta['original_image'] ) ) {
		$files[] = $dir . $meta['original_image'];
	}
	foreach ( (array) ( isset( $meta['sizes'] ) ? $meta['sizes'] : array() ) as $s ) {
		if ( ! empty( $s['file'] ) ) {
			$files[] = $dir . $s['file'];
		}
	}
	return in_array( $rel, $files, true );
}

/** Các thẻ ảnh còn cần xử lý trong một nội dung: mỗi phần tử có tag, src, rel. */
function skb_anh_tim( $html ) {
	$up   = wp_upload_dir();
	$base = trailingslashit( $up['baseurl'] );
	$out  = array();
	if ( ! preg_match_all( '#<img\b[^>]*>#i', (string) $html, $m ) ) {
		return $out;
	}
	foreach ( array_unique( $m[0] ) as $tag ) {
		if ( false !== strpos( $tag, 'skb-anh' ) || ! preg_match( '#\ssrc=(["\'])(.*?)\1#i', $tag, $s ) ) {
			continue;
		}
		$rel = skb_anh_rel( $s[2], $base );
		if ( null === $rel ) {
			continue;
		}
		if ( preg_match( '#\bwp-image-(\d+)\b#', $tag, $w ) && skb_anh_la_cua( (int) $w[1], $rel ) ) {
			continue; // đã là ảnh Thư viện của chính file này (số wp-image cũ từ web cũ thì không tính)
		}
		$out[] = array( 'tag' => $tag, 'src' => $s[2], 'rel' => $rel );
	}
	return $out;
}

function skb_anh_f_nguong() {
	return 2048;
}
function skb_anh_f_co( $sizes ) {
	return array_intersect_key( $sizes, array_flip( array( 'medium', 'medium_large', 'large', '1536x1536' ) ) );
}
function skb_anh_f_webp( $map ) {
	$map['image/jpeg'] = 'image/webp';
	$map['image/png']  = 'image/webp';
	return $map;
}
function skb_anh_f_q( $q, $mime = '' ) {
	return 'image/webp' === $mime ? 78 : 82;
}
function skb_anh_bo_loc( $on ) {
	$fs = array(
		array( 'big_image_size_threshold', 'skb_anh_f_nguong', 1 ),
		array( 'intermediate_image_sizes_advanced', 'skb_anh_f_co', 1 ),
		array( 'image_editor_output_format', 'skb_anh_f_webp', 1 ),
		array( 'wp_editor_set_quality', 'skb_anh_f_q', 2 ),
	);
	foreach ( $fs as $f ) {
		if ( $on ) {
			add_filter( $f[0], $f[1], 99, $f[2] );
		} else {
			remove_filter( $f[0], $f[1], 99 );
		}
	}
}

/** Ảnh Thư viện cho một file (tạo mới nếu chưa có). */
function skb_anh_att( $rel, $parent, $alt ) {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$up   = wp_upload_dir();
	$file = trailingslashit( $up['basedir'] ) . $rel;
	$id   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_skb_anh_nguon' AND meta_value = %s LIMIT 1", $rel ) );
	if ( $id && 'attachment' !== get_post_type( $id ) ) {
		$id = 0;
	}
	if ( ! $id ) {
		if ( ! file_exists( $file ) ) {
			return new WP_Error( 'miss', 'không thấy file ' . $rel );
		}
		$type = wp_check_filetype( $file );
		$id   = wp_insert_attachment(
			array(
				'post_mime_type' => $type['type'],
				'post_title'     => sanitize_text_field( pathinfo( $rel, PATHINFO_FILENAME ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$file,
			$parent,
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		update_post_meta( $id, '_skb_anh_nguon', $rel );
		if ( '' !== $alt ) {
			update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}
	}
	$meta = wp_get_attachment_metadata( $id );
	if ( empty( $meta['width'] ) || empty( $meta['sizes'] ) ) {
		skb_anh_bo_loc( true );
		$meta = wp_generate_attachment_metadata( $id, file_exists( $file ) ? $file : get_attached_file( $id ) );
		skb_anh_bo_loc( false );
		if ( empty( $meta['width'] ) ) {
			return new WP_Error( 'meta', 'không tạo được bản nhỏ cho ' . $rel );
		}
		wp_update_attachment_metadata( $id, $meta );
	}
	return (int) $id;
}

/** Thẻ <img> mới: giữ thuộc tính khác (alt, title, style...), đổi src/width/height/class. */
function skb_anh_the( $tag, $id ) {
	$img = wp_get_attachment_image_src( $id, 'large' );
	if ( ! $img ) {
		return null;
	}
	$t   = preg_replace( '#\s(srcset|sizes|width|height|loading|decoding|fetchpriority|data-src|data-lazy-src|data-srcset)=(["\']).*?\2#i', '', $tag );
	$t   = preg_replace( '#\ssrc=(["\']).*?\1#i', ' src="' . esc_url( $img[0] ) . '" width="' . (int) $img[1] . '" height="' . (int) $img[2] . '"', $t, 1 );
	$cls = preg_match( '#\sclass=(["\'])(.*?)\1#i', $t, $c ) ? $c[2] : '';
	$cls = trim( preg_replace( '#\s+#', ' ', preg_replace( '#\b(wp-image-\d+|size-[\w-]+|fade-img|is-loaded)\b#', '', $cls ) . ' wp-image-' . $id . ' size-large skb-anh' ) );
	if ( $c ) {
		$t = preg_replace( '#\sclass=(["\']).*?\1#i', ' class="' . esc_attr( trim( $cls ) ) . '"', $t, 1 );
	} else {
		$t = preg_replace( '#^<img\b#i', '<img class="' . esc_attr( trim( $cls ) ) . '"', $t, 1 );
	}
	return $t;
}

/** Xử lý một bài trong khoảng thời gian cho phép; gọi lại cho tới khi con = 0. */
function skb_anh_xu_ly( $post_id, $budget = 15.0 ) {
	global $wpdb;
	$post = get_post( $post_id );
	if ( ! $post || ! in_array( $post->post_type, skb_anh_loai(), true ) ) {
		return new WP_Error( 'post', 'không thấy bài #' . $post_id );
	}
	$html  = (string) $post->post_content;
	$list  = skb_anh_tim( $html );
	$start = microtime( true );
	$xong  = 0;
	$loi   = array();
	if ( $list && '' === (string) get_post_meta( $post_id, '_skb_nd_truoc_anh', true ) ) {
		add_post_meta( $post_id, '_skb_nd_truoc_anh', wp_slash( $html ), true );
	}
	foreach ( $list as $it ) {
		if ( ( $xong || $loi ) && microtime( true ) - $start > $budget ) {
			break;
		}
		$alt = preg_match( '#\salt=(["\'])(.*?)\1#i', $it['tag'], $a ) ? html_entity_decode( $a[2] ) : '';
		$att = skb_anh_att( $it['rel'], $post_id, $alt );
		$new = is_wp_error( $att ) ? null : skb_anh_the( $it['tag'], $att );
		if ( ! $new ) {
			$loi[] = is_wp_error( $att ) ? $att->get_error_message() : 'lỗi ' . $it['rel'];
			// đánh dấu để không thử lại mãi; giữ nguyên ảnh
			$new = preg_match( '#\sclass=(["\'])#i', $it['tag'] ) ? preg_replace( '#\sclass=(["\'])#i', ' class=$1skb-anh skb-anh-loi ', $it['tag'], 1 ) : preg_replace( '#^<img\b#i', '<img class="skb-anh skb-anh-loi"', $it['tag'], 1 );
			$html = str_replace( $it['tag'], $new, $html );
			continue;
		}
		$html = str_replace( $it['tag'], $new, $html );
		$url  = wp_get_attachment_url( $att );
		foreach ( array( '"', "'" ) as $q ) {
			$html = str_replace( 'href=' . $q . $it['src'] . $q, 'href=' . $q . esc_url( $url ) . $q, $html );
		}
		$xong++;
	}
	if ( $html !== $post->post_content ) {
		$wpdb->update( $wpdb->posts, array( 'post_content' => $html ), array( 'ID' => $post_id ) );
		clean_post_cache( $post_id );
		if ( function_exists( 'skb_cache_xoa' ) ) {
			skb_cache_xoa();
		}
	}
	return array(
		'title' => get_the_title( $post_id ),
		'xong'  => $xong,
		'con'   => count( skb_anh_tim( $html ) ),
		'loi'   => $loi,
	);
}

/** Danh sách bài còn ảnh cần xử lý. */
add_action( 'wp_ajax_skb_anh_ds', function () {
	skb_anh_guard();
	global $wpdb;
	$types = "'" . implode( "','", array_map( 'esc_sql', skb_anh_loai() ) ) . "'";
	$rows  = $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type IN ($types) AND post_status IN ('publish','draft','private','pending','future') AND post_content LIKE '%<img%' ORDER BY post_type, menu_order, ID" ); // phpcs:ignore
	$out   = array();
	$tong  = 0;
	foreach ( $rows as $r ) {
		$n = count( skb_anh_tim( $r->post_content ) );
		if ( $n ) {
			$out[] = array( 'id' => (int) $r->ID, 'n' => $n, 'title' => get_the_title( $r->ID ) );
			$tong += $n;
		}
	}
	wp_send_json_success( array( 'bai' => $out, 'tong' => $tong ) );
} );

add_action( 'wp_ajax_skb_anh_bai', function () {
	skb_anh_guard();
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	if ( ! ctype_digit( $id ) ) {
		$p  = get_page_by_path( $id, OBJECT, skb_anh_loai() );
		$id = $p ? $p->ID : 0;
	}
	$r = skb_anh_xu_ly( (int) $id );
	if ( is_wp_error( $r ) ) {
		wp_send_json_error( $r->get_error_message() );
	}
	$r['id'] = (int) $id;
	wp_send_json_success( $r );
} );

add_action( 'wp_ajax_skb_anh_hoan_tac', function () {
	skb_anh_guard();
	global $wpdb;
	$id  = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$ids = array();
	if ( 'all' === $id ) {
		$ids = array_map( 'intval', $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_skb_nd_truoc_anh'" ) ); // phpcs:ignore
	} else {
		if ( ! ctype_digit( $id ) ) {
			$p  = get_page_by_path( $id, OBJECT, skb_anh_loai() );
			$id = $p ? $p->ID : 0;
		}
		$ids = array( (int) $id );
	}
	$n = 0;
	foreach ( $ids as $pid ) {
		$old = get_post_meta( $pid, '_skb_nd_truoc_anh', true );
		if ( '' === (string) $old ) {
			continue;
		}
		$wpdb->update( $wpdb->posts, array( 'post_content' => $old ), array( 'ID' => $pid ) );
		delete_post_meta( $pid, '_skb_nd_truoc_anh' );
		clean_post_cache( $pid );
		$n++;
	}
	if ( $n && function_exists( 'skb_cache_xoa' ) ) {
		skb_cache_xoa();
	}
	wp_send_json_success( array( 'n' => $n ) );
} );

function skb_anh_page() {
	global $wpdb;
	$da  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_skb_anh_nguon'" ); // phpcs:ignore
	$bai = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_skb_nd_truoc_anh'" ); // phpcs:ignore
	$webp = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
	?>
	<div class="wrap">
		<h1>Tối ưu ảnh trong nội dung</h1>
		<p>Ảnh trong mô tả mẫu xe và bài viết chép từ web cũ đang là file gốc (0,5–5 MB mỗi ảnh). Công cụ đưa từng ảnh vào Thư viện, thu về tối đa 2048px và tạo bản nhỏ <?php echo $webp ? 'WebP' : '(máy chủ không hỗ trợ WebP — dùng JPEG)'; ?> 300 / 768 / 1024 / 1536px, rồi đổi ảnh trong nội dung để mỗi máy chỉ tải cỡ vừa đủ và chỉ tải khi cuộn tới.</p>
		<p>Đã đưa vào Thư viện: <strong><?php echo (int) $da; ?></strong> ảnh · Bài đã đổi (hoàn tác được): <strong><?php echo (int) $bai; ?></strong>. File gốc không bị xoá; nội dung cũ của mỗi bài được lưu để hoàn tác.</p>
		<p>
			<input type="text" id="skb-anh-id" class="regular-text" placeholder="ID hoặc đường dẫn, ví dụ: led-noi-that-vinfast-vf8">
			<button class="button" id="skb-anh-mot">Chạy một bài</button>
			<button class="button" id="skb-anh-ht">Hoàn tác bài này</button>
		</p>
		<p>
			<button class="button" id="skb-anh-dem">Đếm ảnh còn lại</button>
			<button class="button button-primary" id="skb-anh-het">Chạy tất cả</button>
			<button class="button" id="skb-anh-dung" disabled>Dừng</button>
			<button class="button" id="skb-anh-ht-het">Hoàn tác tất cả</button>
		</p>
		<pre id="skb-anh-log" style="background:#111;color:#ddd;padding:12px;max-height:480px;overflow:auto;white-space:pre-wrap"></pre>
	</div>
	<script>
	(function () {
		var nonce = <?php echo wp_json_encode( wp_create_nonce( 'skb_anh' ) ); ?>, stop = false;
		var log = document.getElementById('skb-anh-log'), $ = function (i) { return document.getElementById(i); };
		function say(t) { log.textContent += t + '\n'; log.scrollTop = log.scrollHeight; }
		async function call(data) {
			data._n = nonce;
			for (var k = 0; k < 4; k++) {
				try {
					var r = await fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(data) });
					var t = await r.text();
					try { return JSON.parse(t); } catch (e) { if (k === 3) return { success: false, data: 'HTTP ' + r.status + ' ' + t.slice(0, 120) }; }
				} catch (e) { if (k === 3) return { success: false, data: e.message }; }
				await new Promise(function (s) { setTimeout(s, 2000); });
			}
		}
		async function mot(id, ten) {
			var tong = 0, vong = 0;
			while (!stop && vong < 200) {
				vong++;
				var r = await call({ action: 'skb_anh_bai', id: id });
				if (!r || !r.success) { say('  ' + (ten || id) + ' → LỖI ' + (r ? r.data : '')); return { xong: tong, loi: 1 }; }
				tong += r.data.xong;
				if (r.data.loi.length) say('  ' + r.data.title + ' · bỏ qua: ' + r.data.loi.join('; '));
				if (!r.data.con) { say(r.data.title + ' → xong ' + tong + ' ảnh'); return { xong: tong, loi: 0 }; }
				if (!r.data.xong && !r.data.loi.length) { say('  ' + r.data.title + ' → không tiến triển, dừng bài này'); return { xong: tong, loi: 1 }; }
				say('  ' + r.data.title + ' · ' + tong + ' ảnh, còn ' + r.data.con);
			}
			return { xong: tong, loi: stop ? 0 : 1 };
		}
		$('skb-anh-mot').addEventListener('click', async function () {
			var id = $('skb-anh-id').value.trim(); if (!id) return;
			this.disabled = true; stop = false; var t0 = Date.now();
			await mot(id); say('(' + Math.round((Date.now() - t0) / 1000) + ' giây)');
			this.disabled = false;
		});
		$('skb-anh-ht').addEventListener('click', async function () {
			var id = $('skb-anh-id').value.trim(); if (!id) return;
			var r = await call({ action: 'skb_anh_hoan_tac', id: id });
			say(r.success ? 'Hoàn tác ' + r.data.n + ' bài.' : 'Lỗi: ' + r.data);
		});
		$('skb-anh-ht-het').addEventListener('click', async function () {
			if (!confirm('Trả lại nội dung cũ cho tất cả bài đã đổi ảnh?')) return;
			var r = await call({ action: 'skb_anh_hoan_tac', id: 'all' });
			say(r.success ? 'Hoàn tác ' + r.data.n + ' bài.' : 'Lỗi: ' + r.data);
		});
		$('skb-anh-dem').addEventListener('click', async function () {
			var r = await call({ action: 'skb_anh_ds' });
			if (!r.success) { say('Lỗi: ' + r.data); return; }
			say('Còn ' + r.data.tong + ' ảnh ở ' + r.data.bai.length + ' bài.');
		});
		$('skb-anh-dung').addEventListener('click', function () { stop = true; this.disabled = true; say('Đang dừng sau ảnh hiện tại…'); });
		$('skb-anh-het').addEventListener('click', async function () {
			this.disabled = true; stop = false; $('skb-anh-dung').disabled = false;
			var r = await call({ action: 'skb_anh_ds' });
			if (!r.success) { say('Lỗi: ' + r.data); this.disabled = false; return; }
			var ds = r.data.bai, n = 0, loi = 0, t0 = Date.now();
			say('Bắt đầu: ' + r.data.tong + ' ảnh ở ' + ds.length + ' bài.');
			for (var i = 0; i < ds.length && !stop; i++) {
				say((i + 1) + '/' + ds.length + ' ' + ds[i].title + ' (' + ds[i].n + ' ảnh)');
				var k = await mot(ds[i].id, ds[i].title); n += k.xong; loi += k.loi;
			}
			say((stop ? 'Đã dừng. ' : 'Xong. ') + n + ' ảnh, ' + loi + ' bài có lỗi, ' + Math.round((Date.now() - t0) / 60000) + ' phút.');
			this.disabled = false; $('skb-anh-dung').disabled = true;
		});
	})();
	</script>
	<?php
}
