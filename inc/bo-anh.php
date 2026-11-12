<?php
/**
 * Bộ ảnh của mẫu xe (meta _skb_gallery: danh sách ID ảnh, cách nhau bởi dấu phẩy).
 * Trang mẫu xe hiện ảnh đại diện trước, rồi tới các ảnh trong bộ ảnh (dải ảnh nhỏ bấm đổi ảnh lớn).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_mau_xe', function () {
	add_meta_box( 'skb_bo_anh', 'Bộ ảnh sản phẩm', 'skb_bo_anh_box', 'mau_xe', 'normal', 'high' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'mau_xe' === get_post_type() ) {
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
	}
} );

function skb_bo_anh_box( $post ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_skb_gallery', true ) ) ) );
	wp_nonce_field( 'skb_bo_anh', 'skb_bo_anh_n' );
	?>
	<style>
		#skb-ba-list{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0;padding:0;list-style:none;min-height:20px}
		#skb-ba-list li{position:relative;width:96px;height:96px;border-radius:8px;overflow:hidden;background:#f0f0f1;cursor:move;margin:0}
		#skb-ba-list img{width:100%;height:100%;object-fit:cover;display:block}
		#skb-ba-list .skb-x{position:absolute;top:4px;right:4px;width:22px;height:22px;border:0;border-radius:50%;background:rgba(0,0,0,.65);color:#fff;cursor:pointer;line-height:20px;padding:0}
		#skb-ba-list .ui-sortable-placeholder{visibility:visible!important;border:2px dashed #2271b1;background:transparent}
	</style>
	<p>Ảnh hiện ở đầu trang mẫu xe, sau <strong>ảnh đại diện</strong>. Chọn nhiều ảnh cùng lúc được; kéo để đổi thứ tự, bấm × để bỏ. Nên dùng ảnh ngang, rộng từ 1200px.</p>
	<input type="hidden" name="skb_gallery" id="skb-ba-ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
	<ul id="skb-ba-list">
		<?php foreach ( $ids as $id ) : $t = wp_get_attachment_image_src( $id, 'thumbnail' ); if ( ! $t ) { continue; } ?>
			<li data-id="<?php echo (int) $id; ?>"><img src="<?php echo esc_url( $t[0] ); ?>" alt=""><button type="button" class="skb-x" aria-label="Bỏ ảnh">×</button></li>
		<?php endforeach; ?>
	</ul>
	<p><button type="button" class="button button-primary" id="skb-ba-add">+ Thêm ảnh</button> <button type="button" class="button" id="skb-ba-clear">Bỏ hết</button> <span id="skb-ba-count" class="description"></span></p>
	<script>
	jQuery(function ($) {
		var $list = $('#skb-ba-list'), $ids = $('#skb-ba-ids'), frame;
		function sync() {
			var ids = $list.children('li').map(function () { return $(this).data('id'); }).get();
			$ids.val(ids.join(',')).trigger('change');
			$('#skb-ba-count').text(ids.length ? ids.length + ' ảnh' : 'Chưa có ảnh');
			// trình soạn khối: báo có thay đổi để nút Lưu sáng lên
			try { if (window.wp && wp.data && wp.data.dispatch('core/editor')) { wp.data.dispatch('core/editor').editPost({ meta: { _skb_bo_anh_doi: String(Date.now()) } }); } } catch (e) {}
		}
		$list.sortable({ update: sync, tolerance: 'pointer' });
		$list.on('click', '.skb-x', function () { $(this).closest('li').remove(); sync(); });
		$('#skb-ba-clear').on('click', function () { if (confirm('Bỏ hết ảnh trong bộ ảnh?')) { $list.empty(); sync(); } });
		$('#skb-ba-add').on('click', function (e) {
			e.preventDefault();
			if (!frame) {
				frame = wp.media({ title: 'Chọn ảnh cho mẫu xe', button: { text: 'Thêm vào bộ ảnh' }, library: { type: 'image' }, multiple: 'add' });
				frame.on('select', function () {
					var have = $ids.val().split(',');
					frame.state().get('selection').each(function (a) {
						var id = String(a.id); if (have.indexOf(id) >= 0) return;
						var s = a.get('sizes'), u = (s && s.thumbnail ? s.thumbnail.url : a.get('url'));
						$list.append($('<li>').attr('data-id', id).data('id', id).append($('<img alt="">').attr('src', u), '<button type="button" class="skb-x" aria-label="Bỏ ảnh">×</button>'));
					});
					sync();
				});
			}
			frame.open();
		});
		$('#skb-ba-count').text($list.children().length ? $list.children().length + ' ảnh' : 'Chưa có ảnh');
	});
	</script>
	<?php
}

add_action( 'save_post_mau_xe', function ( $post_id ) {
	if ( ! isset( $_POST['skb_bo_anh_n'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['skb_bo_anh_n'] ) ), 'skb_bo_anh' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$ids = array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $_POST['skb_gallery'] ?? '' ) ) ) ); // phpcs:ignore
	$ids = array_values( array_unique( array_filter( $ids, function ( $id ) {
		return 'attachment' === get_post_type( $id );
	} ) ) );
	if ( $ids ) {
		update_post_meta( $post_id, '_skb_gallery', implode( ',', $ids ) );
	} else {
		delete_post_meta( $post_id, '_skb_gallery' );
	}
} );

/* meta giả để trình soạn khối bật nút Lưu khi đổi bộ ảnh (không dùng ở đâu khác) */
add_action( 'init', function () {
	register_post_meta( 'mau_xe', '_skb_bo_anh_doi', array(
		'type'          => 'string',
		'single'        => true,
		'show_in_rest'  => true,
		'auth_callback' => function ( $a, $k, $id ) {
			return current_user_can( 'edit_post', $id );
		},
	) );
}, 20 );
