<?php
/**
 * Công cụ > Chuyển sản phẩm web cũ
 * Đọc sản phẩm WooCommerce của web cũ (cùng database, tiền tố bảng dawtt_) và tạo Mẫu xe tương ứng.
 * Chỉ đọc dữ liệu web cũ, không sửa gì ở đó. Chạy lại nhiều lần được: mẫu đã chuyển sẽ được cập nhật, ảnh không bị nhân đôi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKB_OLD_PREFIX', 'dawtt_' );
define( 'SKB_OLD_UPLOADS_URL', 'https://sklight.vn/wp-content/uploads/' );

function skb_old_uploads_dir() {
	return dirname( untrailingslashit( ABSPATH ) ) . '/wp-content/uploads/';
}

add_action( 'admin_menu', function () {
	add_management_page( 'Chuyển sản phẩm web cũ', 'Chuyển sản phẩm web cũ', 'manage_options', 'skb-chuyen', 'skb_mig_page' );
} );

function skb_mig_page() {
	global $wpdb;
	$p     = SKB_OLD_PREFIX;
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}posts WHERE post_type='product' AND post_status='publish'" ); // phpcs:ignore
	$done  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} po ON po.ID=pm.post_id WHERE pm.meta_key='_skb_old_id' AND po.post_type='mau_xe'" ); // phpcs:ignore
	?>
	<div class="wrap">
		<h1>Chuyển sản phẩm web cũ sang Mẫu xe</h1>
		<p>Web cũ có <strong><?php echo (int) $total; ?></strong> sản phẩm đang hiển thị. Đã chuyển: <strong id="skb-done"><?php echo (int) $done; ?></strong>.</p>
		<p>Mỗi sản phẩm thành một Mẫu xe: giữ tên, đường dẫn (slug), mô tả, giá, tiêu đề và mô tả SEO, nhóm hãng/dòng xe và toàn bộ ảnh. Web cũ không bị thay đổi. Chạy lại sẽ cập nhật mẫu đã có, không tạo trùng.</p>
		<p><button class="button button-primary" id="skb-run">Bắt đầu chuyển</button> <label><input type="checkbox" id="skb-img" checked> Sao chép ảnh</label></p>
		<pre id="skb-log" style="background:#111;color:#ddd;padding:12px;max-height:420px;overflow:auto;white-space:pre-wrap"></pre>
		<hr>
		<h2>Bài viết và ảnh trong nội dung</h2>
		<p>Chuyển tin tức, hướng dẫn sử dụng (kèm ảnh đại diện và ảnh trong bài). Chính sách bảo mật và Điều khoản sử dụng được tạo thành Trang. Nút thứ hai sao chép ảnh nằm trong phần mô tả của các mẫu xe đã chuyển về thư viện web mới để không phụ thuộc web cũ.</p>
		<p><button class="button button-primary" id="skb-run-bai">Chuyển bài viết</button> <button class="button" id="skb-run-anh">Sao chép ảnh trong mô tả mẫu xe</button></p>
		<hr>
		<h2>Đại lý</h2>
		<p>Đọc danh sách đại lý ở trang Đại lý của web cũ (tên, địa chỉ, SĐT, giờ mở cửa, tỉnh, link chỉ đường, ảnh riêng của đại lý). Đại lý trùng tên và địa chỉ được cập nhật, không tạo trùng; đại lý chỉ có ở web mới được giữ nguyên.</p>
		<p><button class="button button-primary" id="skb-run-daily">Đồng bộ đại lý từ web cũ</button></p>
		<hr>
		<h2>Gói / biến thể sản phẩm</h2>
		<p>Chép các biến thể của sản phẩm web cũ (ví dụ 18 chi tiết, 22 chi tiết, vành loa) thành gói của mẫu xe, kèm giá bán, giá gốc và ảnh từng gói. Chạy lại sẽ ghi đè gói bằng dữ liệu web cũ.</p>
		<p><button class="button button-primary" id="skb-run-goi">Chuyển gói sản phẩm</button></p>
		<hr>
		<h2>Toạ độ đại lý (nút "Đại lý gần tôi")</h2>
		<p>Lấy toạ độ từng đại lý: ô Toạ độ nhập tay → link chỉ đường Google Maps → địa chỉ (OpenStreetMap) → giữa tỉnh. Sửa đại lý thì tự lấy lại.</p>
		<p><button class="button button-primary" id="skb-run-toado">Lấy toạ độ đại lý</button></p>
	</div>
	<script>
	(function () {
		var nonce = <?php echo wp_json_encode( wp_create_nonce( 'skb_mig' ) ); ?>, url = ajaxurl;
		var log = document.getElementById('skb-log');
		function say(t) { log.textContent += t + '\n'; log.scrollTop = log.scrollHeight; }
		function call(data) {
			data.action = data.action; data._n = nonce;
			return fetch(url, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(data) }).then(function (r) { return r.json(); });
		}
		document.getElementById('skb-run').addEventListener('click', async function () {
			this.disabled = true;
			var withImg = document.getElementById('skb-img').checked, ok = 0, fail = 0;
			var list = await call({ action: 'skb_mig_list' });
			if (!list.success) { say('Lỗi: ' + JSON.stringify(list)); return; }
			say('Tìm thấy ' + list.data.length + ' sản phẩm.');
			for (var i = 0; i < list.data.length; i++) {
				var it = list.data[i];
				try {
					var r = await call({ action: 'skb_mig_post', id: it.id });
					if (!r.success) throw new Error(r.data || 'lỗi');
					var d = r.data, imgOk = 0;
					if (withImg) {
						for (var j = 0; j < d.imgs.length; j++) {
							var ri = await call({ action: 'skb_mig_img', att: d.imgs[j], post: d.new_id, thumb: j === 0 ? 1 : 0 });
							if (ri.success) imgOk++;
						}
					}
					ok++;
					say((i + 1) + '. ' + it.title + ' → ' + d.msg + (withImg ? ' · ảnh ' + imgOk + '/' + d.imgs.length : ''));
				} catch (e) { fail++; say((i + 1) + '. ' + it.title + ' → LỖI ' + e.message); }
				document.getElementById('skb-done').textContent = ok;
			}
			say('Xong: ' + ok + ' thành công, ' + fail + ' lỗi.');
			this.disabled = false;
		});
		document.getElementById('skb-run-bai').addEventListener('click', async function () {
			this.disabled = true;
			var list = await call({ action: 'skb_mig_bai_list' }), ok = 0, fail = 0;
			if (!list.success) { say('Lỗi: ' + JSON.stringify(list)); return; }
			say('Tìm thấy ' + list.data.length + ' bài viết.');
			for (var i = 0; i < list.data.length; i++) {
				var it = list.data[i];
				try {
					var r = await call({ action: 'skb_mig_bai', id: it.id });
					if (!r.success) throw new Error(r.data || 'lỗi');
					var d = r.data, im = '';
					if (d.thumb) { var ri = await call({ action: 'skb_mig_img', att: d.thumb, post: d.new_id, thumb: 1 }); im = ri.success ? ' · có ảnh đại diện' : ' · lỗi ảnh đại diện'; }
					ok++; say((i + 1) + '. ' + d.title + ' → ' + d.msg + im);
				} catch (e) { fail++; say((i + 1) + '. ' + it.title + ' → LỖI ' + e.message); }
			}
			say('Bài viết xong: ' + ok + ' thành công, ' + fail + ' lỗi.');
			this.disabled = false;
		});
		document.getElementById('skb-run-anh').addEventListener('click', async function () {
			this.disabled = true;
			var list = await call({ action: 'skb_mig_noidung_list' }), n = 0, files = 0, fail = 0;
			if (!list.success) { say('Lỗi: ' + JSON.stringify(list)); return; }
			say('Kiểm tra ảnh trong ' + list.data.length + ' nội dung đã chuyển.');
			for (var i = 0; i < list.data.length; i++) {
				var r = await call({ action: 'skb_mig_noidung', id: list.data[i] });
				if (r.success) { n++; files += r.data.files; if (r.data.miss) { fail += r.data.miss; say('#' + list.data[i] + ': thiếu ' + r.data.miss + ' ảnh'); } } else { say('#' + list.data[i] + ' → LỖI ' + r.data); }
			}
			say('Ảnh trong nội dung xong: ' + n + ' bài, ' + files + ' ảnh đã có trên web mới, ' + fail + ' ảnh không tìm thấy.');
			this.disabled = false;
		});
		document.getElementById('skb-run-toado').addEventListener('click', async function () {
			this.disabled = true;
			var names = { tay: 'nhập tay', link: 'từ link chỉ đường', dia_chi: 'theo địa chỉ', tinh: 'giữa tỉnh (gần đúng)', '': 'không lấy được' };
			var list = await call({ action: 'skb_dl_toa_do_list' });
			if (!list.success) { say('Lỗi: ' + JSON.stringify(list)); this.disabled = false; return; }
			for (var i = 0; i < list.data.length; i++) {
				try {
					var r = await call({ action: 'skb_dl_toa_do_one', id: list.data[i] });
					if (!r.success) throw new Error(r.data || 'lỗi');
					say(r.data.title + ' → ' + (names[r.data.nguon] || r.data.nguon) + (r.data.toa_do ? ' (' + r.data.toa_do + ')' : ''));
				} catch (e) { say('#' + list.data[i] + ' → LỖI ' + e.message); }
			}
			say('Toạ độ đại lý xong.');
			this.disabled = false;
		});
		document.getElementById('skb-run-goi').addEventListener('click', async function () {
			this.disabled = true;
			var list = await call({ action: 'skb_mig_goi_list' }), n = 0, g = 0, fail = 0;
			if (!list.success) { say('Lỗi: ' + JSON.stringify(list)); this.disabled = false; return; }
			say('Kiểm tra gói của ' + list.data.length + ' mẫu xe.');
			for (var i = 0; i < list.data.length; i++) {
				try {
					var r = await call({ action: 'skb_mig_goi', id: list.data[i] });
					if (!r.success) throw new Error(r.data || 'lỗi');
					if (r.data.n) { n++; g += r.data.n; say(r.data.title + ' → ' + r.data.n + ' gói: ' + r.data.msg); }
				} catch (e) { fail++; say('#' + list.data[i] + ' → LỖI ' + e.message); }
			}
			say('Gói xong: ' + n + ' mẫu có gói, tổng ' + g + ' gói, ' + fail + ' lỗi.');
			this.disabled = false;
		});
		document.getElementById('skb-run-daily').addEventListener('click', async function () {
			this.disabled = true;
			var list = await call({ action: 'skb_mig_daily_list' }), ok = 0, fail = 0;
			if (!list.success) { say('Lỗi: ' + JSON.stringify(list)); this.disabled = false; return; }
			say('Tìm thấy ' + list.data.length + ' đại lý ở web cũ.');
			for (var i = 0; i < list.data.length; i++) {
				try {
					var r = await call({ action: 'skb_mig_daily_one', idx: i });
					if (!r.success) throw new Error(r.data || 'lỗi');
					var d = r.data, im = '';
					if (d.thumb) { var ri = await call({ action: 'skb_mig_img', att: d.thumb, post: d.new_id, thumb: 1 }); im = ri.success ? ' · có ảnh' : ' · lỗi ảnh'; }
					ok++; say((i + 1) + '. ' + d.title + ' (' + d.tinh + ') → ' + d.msg + im);
				} catch (e) { fail++; say((i + 1) + '. ' + list.data[i] + ' → LỖI ' + e.message); }
			}
			say('Đại lý xong: ' + ok + ' thành công, ' + fail + ' lỗi.');
			this.disabled = false;
		});
	})();
	</script>
	<?php
}

function skb_mig_guard() {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'skb_mig', '_n', false ) ) {
		wp_send_json_error( 'Không có quyền hoặc phiên đã hết hạn.' );
	}
}

add_action( 'wp_ajax_skb_mig_list', function () {
	skb_mig_guard();
	global $wpdb;
	$p    = SKB_OLD_PREFIX;
	$rows = $wpdb->get_results( "SELECT ID AS id, post_title AS title FROM {$p}posts WHERE post_type='product' AND post_status='publish' ORDER BY menu_order, ID" ); // phpcs:ignore
	wp_send_json_success( $rows );
} );

/* Hãng xe: tên trên web cũ → tên trên web mới */
function skb_mig_brand_name( $name ) {
	$alias = array( 'vinfast' => 'VinFast', 'huyndai' => 'Hyundai', 'hyundai' => 'Hyundai', 'kia' => 'Kia', 'mg' => 'MG', 'bmw' => 'BMW' );
	$k     = strtolower( trim( $name ) );
	return isset( $alias[ $k ] ) ? $alias[ $k ] : trim( $name );
}

function skb_mig_term( $name, $parent = 0 ) {
	$found = get_terms( array( 'taxonomy' => 'hang_xe', 'hide_empty' => false, 'parent' => $parent, 'name' => $name ) );
	if ( ! is_wp_error( $found ) && $found ) {
		return (int) $found[0]->term_id;
	}
	$t = wp_insert_term( $name, 'hang_xe', array( 'parent' => $parent ) );
	if ( is_wp_error( $t ) ) {
		return isset( $t->error_data['term_exists'] ) ? (int) $t->error_data['term_exists'] : 0;
	}
	return (int) $t['term_id'];
}

function skb_mig_terms( $old_id ) {
	global $wpdb;
	$p   = SKB_OLD_PREFIX;
	$all = $wpdb->get_results( "SELECT t.term_id, t.name, tt.parent FROM {$p}terms t JOIN {$p}term_taxonomy tt ON tt.term_id=t.term_id WHERE tt.taxonomy='product_cat'", OBJECT_K ); // phpcs:ignore
	$mine = $wpdb->get_col( $wpdb->prepare( "SELECT tt.term_id FROM {$p}term_relationships tr JOIN {$p}term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id WHERE tr.object_id=%d AND tt.taxonomy='product_cat'", $old_id ) ); // phpcs:ignore
	$skip = array( 'sản phẩm khác', 'uncategorized', 'chưa phân loại' );
	$ids  = array();
	foreach ( $mine as $tid ) {
		if ( ! isset( $all[ $tid ] ) || in_array( mb_strtolower( $all[ $tid ]->name ), $skip, true ) ) {
			continue;
		}
		$chain = array();
		$cur   = (int) $tid;
		while ( $cur && isset( $all[ $cur ] ) && count( $chain ) < 5 ) {
			if ( ! in_array( mb_strtolower( $all[ $cur ]->name ), $skip, true ) ) {
				array_unshift( $chain, $all[ $cur ]->name );
			}
			$cur = (int) $all[ $cur ]->parent;
		}
		if ( ! $chain ) {
			continue;
		}
		$brand = skb_mig_term( skb_mig_brand_name( $chain[0] ) );
		$ids[] = $brand;
		if ( count( $chain ) > 1 && $brand ) {
			$ids[] = skb_mig_term( end( $chain ), $brand );
		}
	}
	if ( ! $ids ) {
		$ids[] = skb_mig_term( 'Dòng xe khác' );
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

function skb_mig_clean( $html ) {
	$html = preg_replace( '/\[\/?(ux_[a-z_]+|row|col|section|gap|title|button|divider|accordion[a-z_]*|tabgroup|tab)\b[^\]]*\]/i', '', (string) $html );
	return trim( $html );
}

add_action( 'wp_ajax_skb_mig_post', function () {
	skb_mig_guard();
	global $wpdb;
	$p  = SKB_OLD_PREFIX;
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
	$o  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}posts WHERE ID=%d AND post_type='product'", $id ) ); // phpcs:ignore
	if ( ! $o ) {
		wp_send_json_error( 'Không thấy sản phẩm cũ #' . $id );
	}
	$meta = array();
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$p}postmeta WHERE post_id=%d", $id ) ) as $m ) { // phpcs:ignore
		$meta[ $m->meta_key ] = $m->meta_value;
	}
	$excerpt = preg_replace( '#<p>\s*(<span[^>]*>)?\s*<strong>\s*\(\s*Giá đã bao gồm VAT\s*\)\s*</strong>\s*(</span>)?\s*</p>#iu', '', (string) $o->post_excerpt );
	$data    = array(
		'post_type'    => 'mau_xe',
		'post_status'  => 'publish',
		'post_title'   => trim( preg_replace( '/\s+/u', ' ', $o->post_title ) ),
		'post_name'    => $o->post_name,
		'post_content' => skb_mig_local( skb_mig_clean( $o->post_content ) ),
		'post_excerpt' => trim( wp_strip_all_tags( skb_mig_clean( $excerpt ) ) ),
		'post_date'    => $o->post_date,
		'menu_order'   => (int) $o->menu_order,
	);
	$exist = get_posts( array( 'post_type' => 'mau_xe', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_skb_old_id', 'meta_value' => $id ) ); // phpcs:ignore
	if ( $exist ) {
		$data['ID'] = $exist[0];
		$new        = wp_update_post( wp_slash( $data ), true );
		$msg        = 'cập nhật #' . $exist[0];
	} else {
		$new = wp_insert_post( wp_slash( $data ), true );
		$msg = is_wp_error( $new ) ? '' : 'tạo mới #' . $new;
	}
	if ( is_wp_error( $new ) ) {
		wp_send_json_error( $new->get_error_message() );
	}
	update_post_meta( $new, '_skb_old_id', $id );
	update_post_meta( $new, '_skb_old_url', '/san-pham/' . $o->post_name );
	$price = isset( $meta['_price'] ) && '' !== $meta['_price'] ? $meta['_price'] : ( isset( $meta['_regular_price'] ) ? $meta['_regular_price'] : '' );
	update_post_meta( $new, '_skb_gia', (string) $price );
	update_post_meta( $new, '_skb_gia_goc', isset( $meta['_regular_price'] ) ? (string) $meta['_regular_price'] : '' );
	foreach ( array( '_dawp_seo_title' => '_skb_seo_title', '_dawp_seo_desc' => '_skb_seo_desc' ) as $from => $to ) {
		if ( ! empty( $meta[ $from ] ) ) {
			update_post_meta( $new, $to, sanitize_text_field( $meta[ $from ] ) );
		}
	}
	wp_set_object_terms( $new, skb_mig_terms( $id ), 'hang_xe' );
	$imgs = array();
	if ( ! empty( $meta['_thumbnail_id'] ) ) {
		$imgs[] = (int) $meta['_thumbnail_id'];
	}
	if ( ! empty( $meta['_product_image_gallery'] ) ) {
		$imgs = array_merge( $imgs, array_map( 'intval', explode( ',', $meta['_product_image_gallery'] ) ) );
	}
	$imgs = array_values( array_unique( array_filter( $imgs ) ) );
	delete_post_meta( $new, '_skb_gallery' );
	wp_send_json_success( array( 'new_id' => $new, 'imgs' => $imgs, 'msg' => $msg ) );
} );

add_action( 'wp_ajax_skb_mig_img', function () {
	skb_mig_guard();
	global $wpdb;
	$p     = SKB_OLD_PREFIX;
	$old   = isset( $_POST['att'] ) ? absint( $_POST['att'] ) : 0; // phpcs:ignore
	$post  = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0; // phpcs:ignore
	$thumb = ! empty( $_POST['thumb'] ); // phpcs:ignore
	if ( ! $old || ! $post ) {
		wp_send_json_error( 'Thiếu dữ liệu' );
	}
	$att = skb_mig_att_copy( $old, $post );
	if ( is_wp_error( $att ) ) {
		wp_send_json_error( $att->get_error_message() );
	}
	if ( $thumb ) {
		set_post_thumbnail( $post, $att );
	} else {
		$g   = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $post, '_skb_gallery', true ) ) ) );
		$g[] = (int) $att;
		update_post_meta( $post, '_skb_gallery', implode( ',', array_unique( $g ) ) );
	}
	wp_send_json_success( $att );
} );

/* Sao chép một ảnh của web cũ sang thư viện web mới (dùng lại nếu đã chép). Trả về ID ảnh mới hoặc WP_Error. */
function skb_mig_att_copy( $old, $post ) {
	global $wpdb;
	$p = SKB_OLD_PREFIX;
	$have = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_skb_old_att', 'meta_value' => $old ) ); // phpcs:ignore
	if ( $have ) {
		$att = $have[0];
	} else {
		$rel = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$p}postmeta WHERE post_id=%d AND meta_key='_wp_attached_file'", $old ) ); // phpcs:ignore
		if ( ! $rel ) {
			return new WP_Error( 'skb', 'Ảnh cũ #' . $old . ' không có file' );
		}
		$up   = wp_upload_dir();
		$dest = trailingslashit( $up['basedir'] ) . $rel;
		wp_mkdir_p( dirname( $dest ) );
		$src = skb_old_uploads_dir() . $rel;
		if ( ! file_exists( $dest ) ) {
			if ( file_exists( $src ) ) {
				copy( $src, $dest );
			} else {
				$r = wp_remote_get( SKB_OLD_UPLOADS_URL . $rel, array( 'timeout' => 30 ) );
				if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) {
					return new WP_Error( 'skb', 'Không lấy được ' . $rel );
				}
				file_put_contents( $dest, wp_remote_retrieve_body( $r ) ); // phpcs:ignore
			}
		}
		$ft   = wp_check_filetype( $dest );
		$title = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM {$p}posts WHERE ID=%d", $old ) ); // phpcs:ignore
		$att  = wp_insert_attachment( array( 'post_mime_type' => $ft['type'], 'post_title' => $title ? $title : basename( $rel ), 'post_status' => 'inherit' ), $dest, $post );
		if ( is_wp_error( $att ) || ! $att ) {
			return new WP_Error( 'skb', 'Không tạo được ảnh' );
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $dest ) );
		update_post_meta( $att, '_skb_old_att', $old );
		$alt = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$p}postmeta WHERE post_id=%d AND meta_key='_wp_attachment_image_alt'", $old ) ); // phpcs:ignore
		if ( $alt ) {
			update_post_meta( $att, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}
	}
	return (int) $att;
}

/* Ảnh trong nội dung: chép file từ thư mục ảnh web cũ sang web mới (giữ nguyên đường dẫn con) rồi đổi link */
function skb_mig_local( $html, &$stat = null ) {
	$stat = array( 'files' => 0, 'miss' => 0 );
	$up   = wp_upload_dir();
	$html = preg_replace_callback(
		'#https?://(?:www\.)?sklight\.vn/wp-content/uploads/([^\s"\'<>(),]+)#i',
		function ( $m ) use ( $up, &$stat ) {
			$rel  = rawurldecode( $m[1] );
			if ( false !== strpos( $rel, '..' ) ) {
				return $m[0];
			}
			$dest = trailingslashit( $up['basedir'] ) . $rel;
			if ( ! file_exists( $dest ) ) {
				$src = skb_old_uploads_dir() . $rel;
				if ( ! file_exists( $src ) ) {
					$stat['miss']++;
					return $m[0];
				}
				wp_mkdir_p( dirname( $dest ) );
				copy( $src, $dest );
			}
			$stat['files']++;
			return trailingslashit( $up['baseurl'] ) . $m[1];
		},
		(string) $html
	);
	if ( $stat['miss'] ) {
		$html = skb_mig_bo_anh_hong( $html );
	}
	return $html;
}

/* Ảnh mà web cũ cũng không còn file (đang lỗi 404): bỏ thẻ ảnh và khung bao rỗng */
function skb_mig_bo_anh_hong( $html ) {
	$html = preg_replace( '#<img\b[^>]*\bsrc=["\']https?://(?:www\.)?sklight\.vn/wp-content/uploads/[^"\']*["\'][^>]*>#i', '', $html );
	$html = preg_replace( '#\[video\b[^\]]*sklight\.vn/wp-content/uploads/[^\]]*\](\s*\[/video\])?#i', '', $html );
	for ( $i = 0; $i < 3; $i++ ) {
		$html = preg_replace(
			array(
				'#<a\b[^>]*>\s*</a>#i',
				'#<figure\b[^>]*>\s*(<figcaption\b[^>]*>\s*</figcaption>)?\s*</figure>#i',
				'#\[caption[^\]]*\]\s*\[/caption\]#i',
				'#<p\b[^>]*>(\s|&nbsp;|\xC2\xA0)*</p>#i',
				'#<div\b[^>]*>\s*</div>#i',
			),
			'',
			$html
		);
	}
	return trim( $html );
}

add_action( 'wp_ajax_skb_mig_bai_list', function () {
	skb_mig_guard();
	global $wpdb;
	$p    = SKB_OLD_PREFIX;
	$rows = $wpdb->get_results( "SELECT ID AS id, post_title AS title FROM {$p}posts WHERE post_type='post' AND post_status='publish' ORDER BY post_date, ID" ); // phpcs:ignore
	wp_send_json_success( $rows );
} );

add_action( 'wp_ajax_skb_mig_bai', function () {
	skb_mig_guard();
	global $wpdb;
	$p  = SKB_OLD_PREFIX;
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
	$o  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}posts WHERE ID=%d AND post_type='post'", $id ) ); // phpcs:ignore
	if ( ! $o ) {
		wp_send_json_error( 'Không thấy bài cũ #' . $id );
	}
	$meta = array();
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$p}postmeta WHERE post_id=%d", $id ) ) as $m ) { // phpcs:ignore
		$meta[ $m->meta_key ] = $m->meta_value;
	}
	$content = skb_mig_clean( $o->post_content );
	$title   = trim( preg_replace( '/\s+/u', ' ', $o->post_title ) );
	if ( '' === $title && preg_match( '#<h1[^>]*>(.*?)</h1>#is', $content, $h ) ) {
		$title   = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $h[1] ) ) );
		$content = trim( str_replace( $h[0], '', $content ) );
	}
	$content = skb_mig_local( $content );
	$slug    = $o->post_name;
	if ( preg_match( '/^\d+(-\d+)?$/', $slug ) && '' !== $title ) {
		$slug = sanitize_title( remove_accents( $title ) );
	}
	$is_page = in_array( mb_strtolower( $title ), array( 'chính sách bảo mật', 'điều khoản sử dụng' ), true );
	$type    = $is_page ? 'page' : 'post';
	$data    = array(
		'post_type'     => $type,
		'post_status'   => 'publish',
		'post_title'    => $title,
		'post_name'     => $slug,
		'post_content'  => $content,
		'post_excerpt'  => trim( wp_strip_all_tags( (string) $o->post_excerpt ) ),
		'post_date'     => $o->post_date,
		'post_date_gmt' => $o->post_date_gmt,
	);
	$exist = get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_skb_old_id', 'meta_value' => $id ) ); // phpcs:ignore
	if ( $exist ) {
		$data['ID'] = $exist[0];
		$new        = wp_update_post( wp_slash( $data ), true );
		$msg        = ( $is_page ? 'trang' : 'bài' ) . ' cập nhật #' . $exist[0];
	} else {
		$new = wp_insert_post( wp_slash( $data ), true );
		$msg = is_wp_error( $new ) ? '' : ( $is_page ? 'trang' : 'bài' ) . ' mới #' . $new;
	}
	if ( is_wp_error( $new ) ) {
		wp_send_json_error( $new->get_error_message() );
	}
	update_post_meta( $new, '_skb_old_id', $id );
	update_post_meta( $new, '_skb_old_url', '/' . $o->post_name );
	foreach ( array( '_dawp_seo_title' => '_skb_seo_title', '_dawp_seo_desc' => '_skb_seo_desc' ) as $from => $to ) {
		if ( ! empty( $meta[ $from ] ) ) {
			update_post_meta( $new, $to, sanitize_text_field( $meta[ $from ] ) );
		}
	}
	if ( ! $is_page ) {
		$cats = $wpdb->get_results( $wpdb->prepare( "SELECT t.name, t.slug FROM {$p}term_relationships tr JOIN {$p}term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id JOIN {$p}terms t ON t.term_id=tt.term_id WHERE tr.object_id=%d AND tt.taxonomy='category'", $id ) ); // phpcs:ignore
		$slugs = wp_list_pluck( $cats, 'slug' );
		if ( in_array( 'huong-dan-su-dung', $slugs, true ) ) {
			$cats = array( (object) array( 'name' => 'Hướng dẫn sử dụng', 'slug' => 'huong-dan-su-dung' ) );
		}
		$ids = array();
		foreach ( $cats as $c ) {
			if ( 'uncategorized' === $c->slug ) {
				continue;
			}
			$t = get_term_by( 'slug', $c->slug, 'category' );
			if ( ! $t ) {
				$t = wp_insert_term( $c->name, 'category', array( 'slug' => $c->slug ) );
				$ids[] = is_wp_error( $t ) ? 0 : (int) $t['term_id'];
			} else {
				$ids[] = (int) $t->term_id;
			}
		}
		$ids = array_filter( $ids );
		if ( $ids ) {
			wp_set_post_categories( $new, $ids );
		}
	}
	wp_send_json_success( array( 'new_id' => $new, 'title' => $title, 'msg' => $msg, 'thumb' => empty( $meta['_thumbnail_id'] ) ? 0 : (int) $meta['_thumbnail_id'] ) );
} );

add_action( 'wp_ajax_skb_mig_noidung_list', function () {
	skb_mig_guard();
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key='_skb_old_id'" ); // phpcs:ignore
	wp_send_json_success( array_map( 'intval', $ids ) );
} );

add_action( 'wp_ajax_skb_mig_noidung', function () {
	skb_mig_guard();
	$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
	$post = get_post( $id );
	if ( ! $post ) {
		wp_send_json_error( 'Không thấy #' . $id );
	}
	$stat = null;
	$html = skb_mig_local( $post->post_content, $stat );
	if ( $html !== $post->post_content ) {
		wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $html ) ) );
	}
	wp_send_json_success( $stat );
} );

/* Đại lý: đọc shortcode [store_item_t …] ở trang Đại lý của web cũ */
function skb_mig_daily_items() {
	global $wpdb;
	$p       = SKB_OLD_PREFIX;
	$content = (string) $wpdb->get_var( "SELECT post_content FROM {$p}posts WHERE post_type='page' AND post_name='dai-ly' AND post_status='publish' ORDER BY ID LIMIT 1" ); // phpcs:ignore
	$items   = array();
	if ( preg_match_all( '/\[store_item_t\b([^\]]*)\]/u', $content, $m ) ) {
		foreach ( $m[1] as $raw ) {
			$a = shortcode_parse_atts( $raw );
			if ( ! is_array( $a ) || empty( $a['name'] ) ) {
				continue;
			}
			$items[] = $a;
		}
	}
	return $items;
}

function skb_mig_tinh( $province, $address ) {
	$map = array(
		'ha-noi' => 'Hà Nội', 'an-giang' => 'An Giang', 'bac-giang' => 'Bắc Giang', 'bac-kan' => 'Bắc Kạn', 'bac-ninh' => 'Bắc Ninh',
		'ben-tre' => 'Bến Tre', 'binh-duong' => 'Bình Dương', 'tp hồ chí minh' => 'TP. Hồ Chí Minh', 'hồ chí minh' => 'TP. Hồ Chí Minh',
		'cam lâm' => 'Khánh Hòa', 'nha trang' => 'Khánh Hòa', 'khánh hoà' => 'Khánh Hòa',
	);
	$k = mb_strtolower( trim( (string) $province ) );
	if ( isset( $map[ $k ] ) ) {
		return $map[ $k ];
	}
	if ( '' !== $k ) {
		return trim( (string) $province );
	}
	$parts = array_map( 'trim', explode( ',', (string) $address ) );
	return (string) end( $parts );
}

add_action( 'wp_ajax_skb_mig_daily_list', function () {
	skb_mig_guard();
	wp_send_json_success( array_map( function ( $a ) { return $a['name']; }, skb_mig_daily_items() ) );
} );

add_action( 'wp_ajax_skb_mig_daily_one', function () {
	skb_mig_guard();
	global $wpdb;
	$idx   = isset( $_POST['idx'] ) ? absint( $_POST['idx'] ) : 0; // phpcs:ignore
	$items = skb_mig_daily_items();
	if ( ! isset( $items[ $idx ] ) ) {
		wp_send_json_error( 'Không thấy đại lý thứ ' . ( $idx + 1 ) );
	}
	$a     = $items[ $idx ];
	$name  = trim( preg_replace( '/\s+/u', ' ', $a['name'] ) );
	$addr  = trim( preg_replace( '/\s+/u', ' ', $a['address'] ?? '' ) );
	$key   = md5( mb_strtolower( $name . '|' . $addr ) );
	$tinh  = skb_mig_tinh( $a['province'] ?? '', $addr );
	$found = get_posts( array( 'post_type' => 'dai_ly', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_skb_old_key', 'meta_value' => $key ) ); // phpcs:ignore
	if ( ! $found ) {
		// đại lý web mới đã tạo sẵn: cùng tên và cùng địa chỉ (không tính khoảng trắng, hoa thường)
		$norm = function ( $t ) { return preg_replace( '/[\s,.]+/u', '', mb_strtolower( (string) $t ) ); };
		foreach ( get_posts( array( 'post_type' => 'dai_ly', 'post_status' => 'any', 'numberposts' => -1 ) ) as $d ) {
			if ( $norm( $d->post_title ) === $norm( $name ) && $norm( get_post_meta( $d->ID, '_skb_dia_chi', true ) ) === $norm( $addr ) ) {
				$found = array( $d->ID );
				break;
			}
		}
	}
	$data = array( 'post_type' => 'dai_ly', 'post_status' => 'publish', 'post_title' => $name, 'menu_order' => $idx );
	if ( $found ) {
		$data['ID'] = $found[0];
		$id         = wp_update_post( wp_slash( $data ), true );
		$msg        = 'cập nhật #' . $found[0];
	} else {
		$id  = wp_insert_post( wp_slash( $data ), true );
		$msg = is_wp_error( $id ) ? '' : 'tạo mới #' . $id;
	}
	if ( is_wp_error( $id ) ) {
		wp_send_json_error( $id->get_error_message() );
	}
	update_post_meta( $id, '_skb_old_key', $key );
	update_post_meta( $id, '_skb_dia_chi', $addr );
	update_post_meta( $id, '_skb_dien_thoai', trim( $a['phone'] ?? '' ) );
	update_post_meta( $id, '_skb_gio_mo', trim( $a['time'] ?? '' ) );
	update_post_meta( $id, '_skb_tinh', $tinh );
	if ( ! empty( $a['map_url'] ) ) {
		update_post_meta( $id, '_skb_ban_do', esc_url_raw( $a['map_url'] ) );
	}
	// Ảnh: chỉ lấy ảnh riêng của đại lý (web cũ ghi số ảnh); ảnh dạng đường dẫn là ảnh minh hoạ dùng chung
	$img = trim( $a['image'] ?? '' );
	wp_send_json_success( array( 'new_id' => $id, 'title' => $name, 'tinh' => $tinh, 'msg' => $msg, 'thumb' => ctype_digit( $img ) ? (int) $img : 0 ) );
} );

/* Gói / biến thể: biến thể WooCommerce của sản phẩm cũ → gói của mẫu xe (_skb_goi) */
add_action( 'wp_ajax_skb_mig_goi_list', function () {
	skb_mig_guard();
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT po.ID FROM {$wpdb->posts} po JOIN {$wpdb->postmeta} pm ON pm.post_id=po.ID AND pm.meta_key='_skb_old_id' WHERE po.post_type='mau_xe' AND po.post_status<>'trash' ORDER BY po.menu_order, po.ID" ); // phpcs:ignore
	wp_send_json_success( array_map( 'intval', $ids ) );
} );

add_action( 'wp_ajax_skb_mig_goi', function () {
	skb_mig_guard();
	global $wpdb;
	$p   = SKB_OLD_PREFIX;
	$id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
	$old = (int) get_post_meta( $id, '_skb_old_id', true );
	if ( ! $id || ! $old ) {
		wp_send_json_error( 'Mẫu xe #' . $id . ' không có sản phẩm cũ tương ứng' );
	}
	$vars = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_excerpt FROM {$p}posts WHERE post_parent=%d AND post_type='product_variation' AND post_status='publish' ORDER BY menu_order, ID", $old ) ); // phpcs:ignore
	if ( ! $vars ) {
		wp_send_json_success( array( 'title' => get_the_title( $id ), 'n' => 0, 'msg' => 'không có gói' ) );
	}
	$goi = array();
	$err = 0;
	foreach ( $vars as $v ) {
		$meta = array();
		foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$p}postmeta WHERE post_id=%d", $v->ID ) ) as $m ) { // phpcs:ignore
			$meta[ $m->meta_key ] = $m->meta_value;
		}
		$names = array();
		foreach ( $meta as $k => $val ) {
			if ( 0 !== strpos( $k, 'attribute_' ) ) {
				continue;
			}
			$tax = substr( $k, 10 );
			if ( '' === $val ) {
				$names[] = 'Tất cả';
				continue;
			}
			$name = 0 === strpos( $tax, 'pa_' ) ? $wpdb->get_var( $wpdb->prepare( "SELECT t.name FROM {$p}terms t JOIN {$p}term_taxonomy tt ON tt.term_id=t.term_id WHERE tt.taxonomy=%s AND t.slug=%s LIMIT 1", $tax, $val ) ) : $val; // phpcs:ignore
			$names[] = $name ? $name : $val;
		}
		$ten = $names ? implode( ' · ', $names ) : trim( (string) $v->post_excerpt );
		$gia = (int) ( isset( $meta['_price'] ) && '' !== $meta['_price'] ? $meta['_price'] : ( isset( $meta['_regular_price'] ) ? $meta['_regular_price'] : 0 ) );
		$goc = (int) ( isset( $meta['_regular_price'] ) ? $meta['_regular_price'] : 0 );
		$anh = 0;
		if ( ! empty( $meta['_thumbnail_id'] ) ) {
			$a = skb_mig_att_copy( (int) $meta['_thumbnail_id'], $id );
			if ( is_wp_error( $a ) ) {
				$err++;
			} else {
				$anh = $a;
			}
		}
		$goi[] = array(
			'ten'   => $ten,
			'gia'   => $gia,
			'goc'   => $goc > $gia ? $goc : 0,
			'anh'   => $anh,
			'mo_ta' => trim( wp_strip_all_tags( isset( $meta['_variation_description'] ) ? $meta['_variation_description'] : '' ) ),
		);
	}
	$saved = skb_goi_luu( $id, $goi );
	wp_send_json_success( array( 'title' => get_the_title( $id ), 'n' => count( $saved ), 'msg' => implode( ', ', wp_list_pluck( $saved, 'ten' ) ) . ( $err ? ' · ' . $err . ' ảnh lỗi' : '' ) ) );
} );

/* Tối ưu ảnh nội dung (Công cụ › Tối ưu ảnh nội dung) */
require_once __DIR__ . '/toi-uu-anh.php';
