<?php
/**
 * Gói / biến thể của mẫu xe (ví dụ: 18 chi tiết, 22 chi tiết, Vành loa).
 * Lưu ở meta _skb_goi: danh sách { ten, gia, goc, anh, mo_ta }. Giá "từ" (_skb_gia) tự lấy gói rẻ nhất.
 */
defined( 'ABSPATH' ) || exit;

/** Danh sách gói đã chuẩn hoá của một mẫu xe. */
function skb_goi( $post_id = 0 ) {
	$raw = get_post_meta( $post_id ? $post_id : get_the_ID(), '_skb_goi', true );
	$raw = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
	$out = array();
	foreach ( is_array( $raw ) ? $raw : array() as $g ) {
		$ten = isset( $g['ten'] ) ? trim( (string) $g['ten'] ) : '';
		if ( '' === $ten ) {
			continue;
		}
		$out[] = array(
			'ten'   => $ten,
			'gia'   => isset( $g['gia'] ) ? max( 0, (int) $g['gia'] ) : 0,
			'goc'   => isset( $g['goc'] ) ? max( 0, (int) $g['goc'] ) : 0,
			'anh'   => isset( $g['anh'] ) ? (int) $g['anh'] : 0,
			'mo_ta' => isset( $g['mo_ta'] ) ? trim( (string) $g['mo_ta'] ) : '',
		);
	}
	return $out;
}

/** 10700000 → "10.700.000đ" */
function skb_tien( $v ) {
	return $v > 0 ? number_format( (float) $v, 0, ',', '.' ) . 'đ' : '';
}

/** Lưu gói và cập nhật giá "từ" của mẫu xe. */
function skb_goi_luu( $post_id, $goi ) {
	$clean = array();
	foreach ( (array) $goi as $g ) {
		$ten = sanitize_text_field( isset( $g['ten'] ) ? $g['ten'] : '' );
		if ( '' === $ten ) {
			continue;
		}
		$clean[] = array(
			'ten'   => $ten,
			'gia'   => (int) preg_replace( '/\D/', '', (string) ( isset( $g['gia'] ) ? $g['gia'] : '' ) ),
			'goc'   => (int) preg_replace( '/\D/', '', (string) ( isset( $g['goc'] ) ? $g['goc'] : '' ) ),
			'anh'   => absint( isset( $g['anh'] ) ? $g['anh'] : 0 ),
			'mo_ta' => sanitize_text_field( isset( $g['mo_ta'] ) ? $g['mo_ta'] : '' ),
		);
	}
	if ( ! $clean ) {
		delete_post_meta( $post_id, '_skb_goi' );
		return $clean;
	}
	update_post_meta( $post_id, '_skb_goi', wp_slash( wp_json_encode( $clean, JSON_UNESCAPED_UNICODE ) ) );
	if ( function_exists( 'skb_cache_xoa' ) ) {
		skb_cache_xoa();
	}
	$min = null;
	foreach ( $clean as $g ) {
		if ( $g['gia'] > 0 && ( null === $min || $g['gia'] < $min['gia'] ) ) {
			$min = $g;
		}
	}
	if ( $min ) {
		update_post_meta( $post_id, '_skb_gia', (string) $min['gia'] );
		update_post_meta( $post_id, '_skb_gia_goc', $min['goc'] ? (string) $min['goc'] : '' );
	}
	return $clean;
}

/* ---------- Trang sửa mẫu xe: khung "Giá và gói sản phẩm" ---------- */
add_action( 'add_meta_boxes_mau_xe', function () {
	add_meta_box( 'skb_goi_box', 'Giá và gói sản phẩm', 'skb_goi_box', 'mau_xe', 'normal', 'high' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'mau_xe' === get_current_screen()->post_type ) {
		wp_enqueue_media();
	}
} );

function skb_goi_box( $post ) {
	wp_nonce_field( 'skb_goi_save', 'skb_goi_nonce' );
	$goi = skb_goi( $post->ID );
	$gia = get_post_meta( $post->ID, '_skb_gia', true );
	$goc = get_post_meta( $post->ID, '_skb_gia_goc', true );
	?>
	<style>
		.skb-goi-tb{width:100%;border-collapse:collapse}.skb-goi-tb th{text-align:left;font-weight:600;padding:6px 4px}.skb-goi-tb td{padding:6px 4px;vertical-align:top}
		.skb-goi-tb input[type=text]{width:100%}.skb-goi-img{width:56px;height:56px;border:1px dashed #c3c4c7;border-radius:6px;background:#f6f7f7 center/cover no-repeat;cursor:pointer;display:block}
		.skb-goi-act button{margin:0 2px 4px 0}
	</style>
	<p><strong>Mẫu có nhiều gói</strong> (ví dụ 18 chi tiết, 22 chi tiết, vành loa): thêm từng gói bên dưới. Khách chọn gói trên trang, giá và ảnh đổi theo gói. Giá "từ" tự lấy gói rẻ nhất.</p>
	<table class="skb-goi-tb">
		<thead><tr><th style="width:64px">Ảnh</th><th>Tên gói *</th><th style="width:130px">Giá bán (đ)</th><th style="width:130px">Giá gốc (đ)</th><th>Ghi chú ngắn</th><th style="width:96px"></th></tr></thead>
		<tbody id="skb-goi-rows">
		<?php foreach ( array_merge( $goi, array( array( 'ten' => '', 'gia' => '', 'goc' => '', 'anh' => 0, 'mo_ta' => '' ) ) ) as $i => $g ) : $src = $g['anh'] ? wp_get_attachment_image_url( $g['anh'], 'thumbnail' ) : ''; ?>
			<tr class="skb-goi-row">
				<td><a class="skb-goi-img" href="#" title="Chọn ảnh" style="<?php echo $src ? 'background-image:url(' . esc_url( $src ) . ')' : ''; ?>"></a><input type="hidden" data-k="anh" value="<?php echo esc_attr( (string) $g['anh'] ); ?>"></td>
				<td><input type="text" data-k="ten" value="<?php echo esc_attr( $g['ten'] ); ?>" placeholder="Ví dụ: 22 chi tiết"></td>
				<td><input type="text" data-k="gia" inputmode="numeric" value="<?php echo esc_attr( $g['gia'] ? number_format( (float) $g['gia'], 0, ',', '.' ) : '' ); ?>"></td>
				<td><input type="text" data-k="goc" inputmode="numeric" value="<?php echo esc_attr( $g['goc'] ? number_format( (float) $g['goc'], 0, ',', '.' ) : '' ); ?>"></td>
				<td><input type="text" data-k="mo_ta" value="<?php echo esc_attr( $g['mo_ta'] ); ?>" placeholder="Không bắt buộc"></td>
				<td class="skb-goi-act"><button type="button" class="button-link" data-a="up" aria-label="Lên">↑</button><button type="button" class="button-link" data-a="down" aria-label="Xuống">↓</button><button type="button" class="button-link button-link-delete" data-a="del">Xoá</button></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p><button type="button" class="button" id="skb-goi-add">+ Thêm gói</button></p>
	<input type="hidden" name="skb_goi_json" id="skb-goi-json" value="">
	<hr>
	<p><strong>Mẫu chỉ có một giá</strong> (không có gói):
		Giá bán <input type="text" name="skb_gia" inputmode="numeric" value="<?php echo esc_attr( $gia ? number_format( (float) $gia, 0, ',', '.' ) : '' ); ?>" style="width:140px">đ
		&nbsp; Giá gốc <input type="text" name="skb_gia_goc" inputmode="numeric" value="<?php echo esc_attr( $goc ? number_format( (float) $goc, 0, ',', '.' ) : '' ); ?>" style="width:140px">đ
		<br><span class="description">Khi có gói, hai ô này tự lấy theo gói rẻ nhất.</span></p>
	<script>
	(function () {
		var body = document.getElementById('skb-goi-rows'), out = document.getElementById('skb-goi-json');
		var tpl = body.lastElementChild.cloneNode(true);
		function collect() {
			var list = [];
			body.querySelectorAll('.skb-goi-row').forEach(function (tr) {
				var g = {}; tr.querySelectorAll('[data-k]').forEach(function (i) { g[i.dataset.k] = i.value; });
				if (g.ten && g.ten.trim()) list.push(g);
			});
			out.value = JSON.stringify(list);
		}
		document.getElementById('skb-goi-add').addEventListener('click', function () { body.appendChild(tpl.cloneNode(true)); });
		body.addEventListener('click', function (e) {
			var b = e.target.closest('[data-a], .skb-goi-img'); if (!b) return;
			e.preventDefault();
			var tr = b.closest('tr');
			if (b.classList.contains('skb-goi-img')) {
				var f = wp.media({ title: 'Ảnh của gói', multiple: false, library: { type: 'image' } });
				f.on('select', function () {
					var a = f.state().get('selection').first().toJSON();
					tr.querySelector('[data-k=anh]').value = a.id;
					b.style.backgroundImage = 'url(' + ((a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url) + ')';
					collect();
				});
				f.open(); return;
			}
			if (b.dataset.a === 'del') { tr.remove(); if (!body.children.length) body.appendChild(tpl.cloneNode(true)); }
			if (b.dataset.a === 'up' && tr.previousElementSibling) body.insertBefore(tr, tr.previousElementSibling);
			if (b.dataset.a === 'down' && tr.nextElementSibling) body.insertBefore(tr.nextElementSibling, tr);
		});
		// Trình soạn khối đọc thẳng giá trị ô ẩn khi bấm Cập nhật, nên cập nhật ô ẩn sau mỗi thay đổi.
		body.addEventListener('input', collect); body.addEventListener('change', collect);
		new MutationObserver(collect).observe(body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style'] });
		collect();
	})();
	</script>
	<?php
}

add_action( 'save_post_mau_xe', function ( $post_id ) {
	if ( ! isset( $_POST['skb_goi_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skb_goi_nonce'] ) ), 'skb_goi_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$list = json_decode( wp_unslash( isset( $_POST['skb_goi_json'] ) ? $_POST['skb_goi_json'] : '[]' ), true ); // phpcs:ignore
	$saved = skb_goi_luu( $post_id, is_array( $list ) ? $list : array() );
	if ( ! $saved ) {
		foreach ( array( 'skb_gia' => '_skb_gia', 'skb_gia_goc' => '_skb_gia_goc' ) as $f => $k ) {
			if ( isset( $_POST[ $f ] ) ) {
				$v = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) );
				update_post_meta( $post_id, $k, $v );
			}
		}
	}
} );

/**
 * Tách mô tả gói thành từng món để hiện dạng danh sách gọn.
 * "' 4 LED cánh cửa ( 1 LED / cánh cửa ) 2 LED taplo 4 đèn gầm ghế" → ["4 LED cánh cửa (1 LED / cánh cửa)", "2 LED taplo", "4 đèn gầm ghế"]
 */
function skb_goi_muc( $mo_ta ) {
	$s = trim( preg_replace( '/\s+/u', ' ', (string) $mo_ta ) );
	$s = trim( preg_replace( '/^[\'"‘’“”`\-–•,;:.\s]+/u', '', $s ) );
	if ( '' === $s ) {
		return array();
	}
	$s     = preg_replace( array( '/\(\s+/u', '/\s+\)/u' ), array( '(', ')' ), $s );
	$parts = preg_split( '/\s*[,;•]\s*(?=\d)|\s+(?=\d+\s*(?:LED|led|Led|đèn|Đèn|bộ|Bộ|cái|chi tiết)\b)(?![^()]*\))/u', $s );
	$out   = array();
	foreach ( (array) $parts as $p ) {
		$p = trim( $p, " \t,;." );
		if ( '' !== $p ) {
			$out[] = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $p, 0, 1 ) ) . mb_substr( $p, 1 ) : ucfirst( $p );
		}
	}
	return $out;
}

