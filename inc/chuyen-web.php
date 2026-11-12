<?php
/**
 * Chuyển web chính thức (Công cụ › Chuyển web chính thức).
 *
 * Đổi mọi đường dẫn của bản thử (https://sklight.vn/moitest…) trong database của web này sang tên miền gốc:
 * nội dung bài/trang/mẫu xe, meta, tuỳ chọn (kể cả home, siteurl), meta và mô tả chuyên mục.
 * Bắt được: link đầy đủ (http/https, có hoặc không www, //…), link trong JSON (https:\/\/…),
 * link đã mã hoá (%2F), link tương đối "/moitest/…" và đường dẫn thư mục …/public_html/moitest/….
 * Giá trị dạng serialize được mở ra, đổi từng chuỗi rồi đóng lại (không làm hỏng độ dài chuỗi).
 * Mỗi ô bị đổi được lưu bản cũ vào bảng {prefix}skb_chuyen_web để hoàn tác.
 * Chạy theo từng phần nhỏ qua AJAX (máy chủ giới hạn 30 giây mỗi lần).
 */

defined( 'ABSPATH' ) || exit;

const SKB_CW_DB = 1;

function skb_cw_bang_luu() {
	global $wpdb;
	return $wpdb->prefix . 'skb_chuyen_web';
}

/** Các cột cần quét: [bảng, khoá chính, cột, điều kiện thêm] */
function skb_cw_cot() {
	global $wpdb;
	return array(
		array( $wpdb->posts, 'ID', 'post_content', '' ),
		array( $wpdb->posts, 'ID', 'post_excerpt', '' ),
		array( $wpdb->posts, 'ID', 'post_content_filtered', '' ),
		array( $wpdb->postmeta, 'meta_id', 'meta_value', '' ),
		array( $wpdb->options, 'option_id', 'option_value', "AND option_name NOT LIKE '\\_transient\\_%' AND option_name NOT LIKE '\\_site\\_transient\\_%' AND option_name NOT LIKE 'skb\\_cw\\_%'" ),
		array( $wpdb->termmeta, 'meta_id', 'meta_value', '' ),
		array( $wpdb->term_taxonomy, 'term_taxonomy_id', 'description', '' ),
	);
}

add_action( 'admin_menu', function () {
	add_management_page( 'Chuyển web chính thức', 'Chuyển web chính thức', 'manage_options', 'skb-chuyen-web', 'skb_cw_page' );
} );

add_action( 'admin_init', function () {
	if ( (int) get_option( 'skb_cw_db' ) === SKB_CW_DB ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = skb_cw_bang_luu();
	dbDelta( "CREATE TABLE {$t} (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  lan varchar(20) NOT NULL,
  bang varchar(64) NOT NULL,
  khoa bigint unsigned NOT NULL,
  cot varchar(64) NOT NULL,
  cu longtext NOT NULL,
  PRIMARY KEY  (id),
  KEY lan (lan)
) {$wpdb->get_charset_collate()};" );
	update_option( 'skb_cw_db', SKB_CW_DB, false );
} );

function skb_cw_guard() {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'skb_cw', '_n', false ) ) {
		wp_send_json_error( 'Không có quyền hoặc phiên đã hết hạn.' );
	}
	@set_time_limit( 60 ); // phpcs:ignore
}

/** Tách đường dẫn cũ/mới thành host và phần thư mục. */
function skb_cw_cap( $tu, $sang ) {
	$a = wp_parse_url( $tu );
	$b = wp_parse_url( $sang );
	if ( empty( $a['host'] ) || empty( $b['host'] ) ) {
		return null;
	}
	$ha = preg_replace( '/^www\./i', '', strtolower( $a['host'] ) );
	$hb = preg_replace( '/^www\./i', '', strtolower( $b['host'] ) );
	$pa = trim( isset( $a['path'] ) ? $a['path'] : '', '/' );
	$pb = trim( isset( $b['path'] ) ? $b['path'] : '', '/' );
	if ( '' === $pa || $ha . '/' . $pa === $hb . '/' . $pb ) {
		return null;
	}
	return array( 'ha' => $ha, 'pa' => $pa, 'hb' => $hb, 'pb' => $pb );
}

/** Đổi đường dẫn trong một chuỗi thường. */
function skb_cw_doi( $s, $c ) {
	if ( false === stripos( $s, $c['pa'] ) ) {
		return $s;
	}
	$ha = preg_quote( $c['ha'], '~' );
	$pa = preg_quote( $c['pa'], '~' );
	$hb = $c['hb'];
	$pb = '' === $c['pb'] ? '' : '/' . $c['pb'];
	$sau = '(?![\w-]|\.\w)';
	// link đầy đủ: https://, http://, // ; có hoặc không www
	$s = preg_replace( '~(https?:)?//(www\.)?' . $ha . '/' . $pa . $sau . '~i', '${1}//${2}' . $hb . $pb, $s );
	// link trong JSON: https:\/\/sklight.vn\/moitest
	$s = preg_replace( '~(https?:)?\\\\/\\\\/(www\.)?' . $ha . '\\\\/' . $pa . $sau . '~i', '${1}\\/\\/${2}' . $hb . str_replace( '/', '\\/', $pb ), $s );
	// link đã mã hoá: https%3A%2F%2Fsklight.vn%2Fmoitest
	$s = preg_replace( '~(https?%3A)?%2F%2F(www\.)?' . $ha . '%2F' . $pa . $sau . '~i', '${1}%2F%2F${2}' . $hb . str_replace( '/', '%2F', $pb ), $s );
	// tên miền không có giao thức: sklight.vn/moitest/…
	$s = preg_replace( '~(?<![\w./-])(www\.)?' . $ha . '/' . $pa . $sau . '~i', '${1}' . $hb . $pb, $s );
	// link tương đối trong thuộc tính / JSON: "/moitest/…", '\/moitest\/…'
	$s = preg_replace( '~(["\'(=\s,])/' . $pa . '/~', '${1}' . $pb . '/', $s );
	$s = preg_replace( '~^/' . $pa . '(?=/)~', $pb, $s ); // cả ô chỉ là "/moitest/…"
	$s = preg_replace( '~^/' . $pa . '$~', '' === $pb ? '/' : $pb, $s );
	$s = preg_replace( '~(["\'])\\\\/' . $pa . '\\\\/~', '${1}' . str_replace( '/', '\\/', $pb ) . '\\/', $s );
	// đường dẫn thư mục trên máy chủ: …/public_html/moitest/…
	$s = preg_replace( '~/public_html/' . $pa . '(?=/|$)~', '/public_html' . $pb, $s );
	return $s;
}

/** Đổi đệ quy trong mảng (giá trị và khoá chuỗi). Trả null nếu gặp đối tượng có chứa đường dẫn cũ. */
function skb_cw_doi_sau( $v, $c ) {
	if ( is_string( $v ) ) {
		if ( is_serialized( $v ) ) { // chuỗi serialize lồng bên trong
			$r = skb_cw_gia_tri( $v, $c );
			return $r[0];
		}
		return skb_cw_doi( $v, $c );
	}
	if ( is_array( $v ) ) {
		$o = array();
		foreach ( $v as $k => $x ) {
			$x = skb_cw_doi_sau( $x, $c );
			if ( null === $x && null !== $v[ $k ] ) {
				return null;
			}
			$o[ is_string( $k ) ? skb_cw_doi( $k, $c ) : $k ] = $x;
		}
		return $o;
	}
	if ( is_object( $v ) ) {
		return false !== stripos( serialize( $v ), $c['pa'] ) ? null : $v; // phpcs:ignore
	}
	return $v;
}

/**
 * Giá trị mới của một ô. Trả mảng [giá trị mới, ghi chú]; giá trị mới null nghĩa là bỏ qua (không đổi được an toàn).
 */
function skb_cw_gia_tri( $v, $c ) {
	$v = (string) $v;
	if ( is_serialized( $v ) ) {
		$u = @unserialize( $v, array( 'allowed_classes' => false ) ); // phpcs:ignore
		if ( false === $u && 'b:0;' !== $v ) {
			return array( null, 'serialize hỏng' );
		}
		$n = skb_cw_doi_sau( $u, $c );
		if ( null === $n && null !== $u ) {
			return array( null, 'có đối tượng PHP' );
		}
		return array( serialize( $n ), '' ); // phpcs:ignore
	}
	return array( skb_cw_doi( $v, $c ), '' );
}

function skb_cw_ngan( $s, $c ) {
	$i = stripos( $s, $c['pa'] );
	$i = false === $i ? 0 : max( 0, $i - 60 );
	return mb_substr( $s, $i, 160 );
}

/* Một bước: quét/đổi tối đa 300 hàng của một cột. */
add_action( 'wp_ajax_skb_cw_buoc', function () {
	skb_cw_guard();
	global $wpdb;
	$c = skb_cw_cap( wp_unslash( $_POST['tu'] ?? '' ), wp_unslash( $_POST['sang'] ?? '' ) ); // phpcs:ignore
	if ( ! $c ) {
		wp_send_json_error( 'Đường dẫn cũ/mới không hợp lệ.' );
	}
	$lam  = ! empty( $_POST['lam'] ); // phpcs:ignore
	$lan  = preg_replace( '/[^0-9a-z-]/', '', (string) ( $_POST['lan'] ?? '' ) ); // phpcs:ignore
	$i    = (int) ( $_POST['i'] ?? 0 ); // phpcs:ignore
	$sau  = (int) ( $_POST['sau'] ?? 0 ); // phpcs:ignore
	$cols = skb_cw_cot();
	if ( ! isset( $cols[ $i ] ) ) {
		wp_send_json_success( array( 'xong' => true ) );
	}
	if ( $lam && '' === $lan ) {
		wp_send_json_error( 'Thiếu mã lần chạy.' );
	}
	list( $bang, $khoa, $cot, $them ) = $cols[ $i ];
	$like = '%' . $wpdb->esc_like( $c['pa'] ) . '%';
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$khoa} AS k, {$cot} AS v FROM {$bang} WHERE {$khoa} > %d AND {$cot} LIKE %s {$them} ORDER BY {$khoa} LIMIT 300", $sau, $like ) ); // phpcs:ignore
	$doi  = 0;
	$bo   = array();
	$mau  = array();
	$giu  = array();
	foreach ( $rows as $r ) {
		list( $moi, $note ) = skb_cw_gia_tri( $r->v, $c );
		if ( null === $moi ) {
			$bo[] = $bang . '#' . $r->k . ' (' . $note . ')';
			continue;
		}
		if ( $moi === $r->v ) {
			if ( count( $giu ) < 5 ) {
				$giu[] = $bang . '#' . $r->k . ': ' . skb_cw_ngan( $r->v, $c );
			}
			continue;
		}
		++$doi;
		if ( count( $mau ) < 3 ) {
			$mau[] = $bang . '#' . $r->k . ': ' . skb_cw_ngan( $r->v, $c ) . "\n   → " . skb_cw_ngan( $moi, array( 'pa' => $c['hb'] ) + $c );
		}
		if ( $lam ) {
			$ok = $wpdb->insert( skb_cw_bang_luu(), array( 'lan' => $lan, 'bang' => $bang, 'khoa' => (int) $r->k, 'cot' => $cot, 'cu' => $r->v ) );
			if ( ! $ok ) {
				wp_send_json_error( 'Không lưu được bản cũ của ' . $bang . '#' . $r->k . ' — dừng, chưa đổi ô này.' );
			}
			$wpdb->update( $bang, array( $cot => $moi ), array( $khoa => (int) $r->k ) );
		}
	}
	$het = count( $rows ) < 300;
	if ( $lam && $het ) {
		wp_cache_flush();
	}
	wp_send_json_success( array(
		'ten'  => $bang . '.' . $cot,
		'tim'  => count( $rows ),
		'doi'  => $doi,
		'bo'   => $bo,
		'mau'  => $mau,
		'giu'  => $giu,
		'i'    => $het ? $i + 1 : $i,
		'sau'  => $het ? 0 : (int) end( $rows )->k,
		'xong' => $het && ! isset( $cols[ $i + 1 ] ),
	) );
} );

/* Hoàn tác một lần chạy: trả lại bản cũ, mỗi lần tối đa 300 ô, ô đổi sau trả trước. */
add_action( 'wp_ajax_skb_cw_hoan', function () {
	skb_cw_guard();
	global $wpdb;
	$t   = skb_cw_bang_luu();
	$lan = preg_replace( '/[^0-9a-z-]/', '', (string) ( $_POST['lan'] ?? '' ) ); // phpcs:ignore
	if ( '' === $lan ) {
		$lan = (string) $wpdb->get_var( "SELECT lan FROM {$t} ORDER BY id DESC LIMIT 1" ); // phpcs:ignore
	}
	if ( '' === $lan ) {
		wp_send_json_success( array( 'xong' => true, 'n' => 0, 'lan' => '' ) );
	}
	$hop = array();
	foreach ( skb_cw_cot() as $x ) {
		$hop[ $x[0] . '|' . $x[2] ] = $x[1];
	}
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE lan = %s ORDER BY id DESC LIMIT 300", $lan ) ); // phpcs:ignore
	foreach ( $rows as $r ) {
		if ( isset( $hop[ $r->bang . '|' . $r->cot ] ) ) {
			$wpdb->update( $r->bang, array( $r->cot => $r->cu ), array( $hop[ $r->bang . '|' . $r->cot ] => (int) $r->khoa ) );
		}
		$wpdb->delete( $t, array( 'id' => (int) $r->id ) );
	}
	$het = count( $rows ) < 300;
	if ( $het ) {
		wp_cache_flush();
	}
	wp_send_json_success( array( 'xong' => $het, 'n' => count( $rows ), 'lan' => $lan ) );
} );

/* Xoá toàn bộ bộ nhớ đệm trang của máy chủ (cả bản lưu của web cũ). */
add_action( 'wp_ajax_skb_cw_xoa_dem', function () {
	skb_cw_guard();
	global $wpdb;
	// dữ liệu tạm (transient) có thể còn giữ đường dẫn cũ: xoá hết, web tự dựng lại khi cần
	$n = (int) $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%' OR option_name LIKE '\\_site\\_transient\\_%'" ); // phpcs:ignore
	@header( 'X-LiteSpeed-Purge: *', false ); // phpcs:ignore
	wp_cache_flush();
	wp_send_json_success( 'Đã xoá ' . $n . ' dữ liệu tạm và gửi lệnh xoá toàn bộ bộ nhớ đệm LiteSpeed.' );
} );

function skb_cw_page() {
	global $wpdb;
	$t    = skb_cw_bang_luu();
	$lans = (array) $wpdb->get_results( "SELECT lan, COUNT(*) AS n FROM {$t} GROUP BY lan ORDER BY MAX(id) DESC" ); // phpcs:ignore
	$con  = array();
	foreach ( skb_cw_cot() as $x ) {
		$con[ $x[0] . '.' . $x[2] ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$x[0]} WHERE {$x[2]} LIKE %s {$x[3]}", '%' . $wpdb->esc_like( 'moitest' ) . '%' ) ); // phpcs:ignore
	}
	$co = function ( $b ) {
		return $b ? '<span style="color:#1a7f37">có</span>' : '<span style="color:#b32d2e">không</span>';
	};
	$kiem = array(
		'home (tuỳ chọn)'              => esc_html( get_option( 'home' ) ),
		'siteurl (tuỳ chọn)'           => esc_html( get_option( 'siteurl' ) ),
		'WP_HOME trong wp-config'      => defined( 'WP_HOME' ) ? esc_html( WP_HOME ) : '—',
		'WP_SITEURL trong wp-config'   => defined( 'WP_SITEURL' ) ? esc_html( WP_SITEURL ) : '—',
		'Môi trường'                   => esc_html( wp_get_environment_type() ),
		'Cho công cụ tìm kiếm lập chỉ mục' => $co( '1' === (string) get_option( 'blog_public' ) ),
		'SKB_CORE_URL / khoá kích hoạt' => $co( defined( 'SKB_CORE_URL' ) ) . ' / ' . $co( defined( 'SKB_CORE_ACT_SECRET' ) && '' !== SKB_CORE_ACT_SECRET ),
		'Thư mục web'                  => esc_html( ABSPATH ),
		'Thư mục wp-content'           => esc_html( WP_CONTENT_DIR ),
		'Thư mục ảnh'                  => esc_html( wp_upload_dir()['basedir'] ),
	);
	?>
	<div class="wrap">
		<h1>Chuyển web chính thức</h1>
		<p>Đổi mọi đường dẫn của bản thử trong database web này sang tên miền gốc (nội dung, meta, tuỳ chọn kể cả <code>home</code>/<code>siteurl</code>, chuyên mục). Bản cũ của từng ô được lưu để <strong>hoàn tác</strong>. Chỉ bấm "Đổi thật" vào ngày chuyển web, sau khi đã đổi file trên máy chủ.</p>
		<table class="widefat striped" style="max-width:900px"><tbody>
			<?php foreach ( $kiem as $k => $v ) : ?>
				<tr><th style="width:280px"><?php echo esc_html( $k ); ?></th><td><?php echo $v; // phpcs:ignore ?></td></tr>
			<?php endforeach; ?>
			<tr><th>Ô còn chứa "moitest"</th><td><?php echo esc_html( implode( ' · ', array_map( function ( $k, $n ) { return $k . ': ' . $n; }, array_keys( $con ), $con ) ) ); ?></td></tr>
			<tr><th>Bản cũ đã lưu (hoàn tác được)</th><td><?php echo $lans ? esc_html( implode( ' · ', array_map( function ( $r ) { return $r->lan . ': ' . $r->n . ' ô'; }, $lans ) ) ) : 'chưa có'; ?></td></tr>
		</tbody></table>
		<p>
			<label>Đường dẫn cũ <input type="url" id="skb-cw-tu" class="regular-text" value="https://sklight.vn/moitest"></label>
			<label>Đường dẫn mới <input type="url" id="skb-cw-sang" class="regular-text" value="https://sklight.vn"></label>
		</p>
		<p>
			<button class="button" id="skb-cw-thu">Xem trước (không đổi gì)</button>
			<button class="button button-primary" id="skb-cw-lam">Đổi thật</button>
			<button class="button" id="skb-cw-hoan">Hoàn tác lần đổi gần nhất</button>
			<button class="button" id="skb-cw-dem">Xoá toàn bộ bộ nhớ đệm máy chủ</button>
		</p>
		<pre id="skb-cw-log" style="background:#111;color:#ddd;padding:12px;max-height:520px;overflow:auto;white-space:pre-wrap"></pre>
	</div>
	<script>
	(function () {
		var nonce = <?php echo wp_json_encode( wp_create_nonce( 'skb_cw' ) ); ?>;
		var log = document.getElementById('skb-cw-log'), $ = function (i) { return document.getElementById(i); };
		var nut = document.querySelectorAll('.wrap .button');
		function say(t) { log.textContent += t + '\n'; log.scrollTop = log.scrollHeight; }
		function khoa(b) { nut.forEach(function (x) { x.disabled = b; }); }
		async function call(data) {
			data._n = nonce;
			for (var k = 0; k < 4; k++) {
				try {
					var r = await fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(data) });
					var t = await r.text();
					try { return JSON.parse(t); } catch (e) { if (k === 3) return { success: false, data: 'HTTP ' + r.status + ' ' + t.slice(0, 160) }; }
				} catch (e) { if (k === 3) return { success: false, data: e.message }; }
				await new Promise(function (s) { setTimeout(s, 2000); });
			}
		}
		async function quet(lam) {
			var lan = lam ? new Date().toISOString().slice(0, 16).replace(/[^0-9]/g, '') + '-' + Math.random().toString(36).slice(2, 6) : '';
			var st = { i: 0, sau: 0 }, tong = 0, bo = 0;
			say((lam ? 'ĐỔI THẬT' : 'Xem trước') + ': ' + $('skb-cw-tu').value + ' → ' + $('skb-cw-sang').value + (lam ? ' (lần ' + lan + ')' : ''));
			for (;;) {
				var j = await call({ action: 'skb_cw_buoc', tu: $('skb-cw-tu').value, sang: $('skb-cw-sang').value, lam: lam ? 1 : '', lan: lan, i: st.i, sau: st.sau });
				if (!j || !j.success) { say('LỖI: ' + (j && j.data)); return; }
				var d = j.data;
				if (d.ten) {
					tong += d.doi; bo += d.bo.length;
					if (d.tim) say('  ' + d.ten + ': tìm ' + d.tim + ', ' + (lam ? 'đã đổi ' : 'sẽ đổi ') + d.doi + (d.bo.length ? ', bỏ qua ' + d.bo.join(', ') : ''));
					d.mau.forEach(function (m) { say('     ' + m); });
					(d.giu || []).forEach(function (m) { say('   có chữ nhưng không đổi: ' + m); });
				}
				if (d.xong) break;
				st = { i: d.i, sau: d.sau };
			}
			say('Tổng: ' + tong + ' ô ' + (lam ? 'đã đổi' : 'sẽ đổi') + (bo ? ', ' + bo + ' ô bỏ qua (xem trên)' : '') + '.');
			if (lam) say('Tải lại trang này để xem kết quả kiểm tra.');
		}
		$('skb-cw-thu').addEventListener('click', async function () { khoa(true); await quet(false); khoa(false); });
		$('skb-cw-lam').addEventListener('click', async function () {
			if (!confirm('Đổi thật mọi đường dẫn ' + $('skb-cw-tu').value + ' → ' + $('skb-cw-sang').value + '?\nBản cũ được lưu để hoàn tác.')) return;
			khoa(true); await quet(true); khoa(false);
		});
		$('skb-cw-hoan').addEventListener('click', async function () {
			if (!confirm('Trả lại toàn bộ đường dẫn của lần đổi gần nhất?')) return;
			khoa(true); var n = 0, lan = '';
			for (;;) {
				var j = await call({ action: 'skb_cw_hoan', lan: lan });
				if (!j || !j.success) { say('LỖI: ' + (j && j.data)); break; }
				lan = j.data.lan; n += j.data.n;
				if (j.data.xong) { say('Đã hoàn tác ' + n + ' ô' + (lan ? ' (lần ' + lan + ')' : '') + '. Tải lại trang để kiểm tra.'); break; }
			}
			khoa(false);
		});
		$('skb-cw-dem').addEventListener('click', async function () {
			var j = await call({ action: 'skb_cw_xoa_dem' });
			say(j && j.success ? j.data : 'LỖI: ' + (j && j.data));
		});
	})();
	</script>
	<?php
}
