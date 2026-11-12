<?php
/**
 * Các khối chỉ có ở trang chủ tiếng Việt (0.16.0): nỗi lo, dải số liệu, hỏi đáp, bài viết mới.
 * Gọi: get_template_part( 'template-parts/trang-chu-vi', null, array( 'khoi' => 'noi-lo' ) );
 */
defined( 'ABSPATH' ) || exit;

$skb_khoi    = isset( $args['khoi'] ) ? $args['khoi'] : '';
$skb_hotline = skb_opt( 'hotline', '0868 055 555' );

if ( 'noi-lo' === $skb_khoi ) :
	$skb_lo = array(
		array( 'Sợ phải cắt dây, mất zin?', 'Không cắt chích dây. Tấm ốp thay đúng vị trí ốp zin của từng dòng xe và đấu bằng giắc chờ.' ),
		array( 'Sợ lắp xong trông như đồ độ?', 'Ốp làm riêng theo từng đời xe, nên nội thất vẫn giữ dáng nguyên bản.' ),
		array( 'Sợ lắp rồi không ai lo?', 'Lắp tại đại lý SK và được bảo hành 24 tháng, tính từ ngày kích hoạt.' ),
	);
	?>
<section class="lo" aria-labelledby="lo-title">
	<h2 id="lo-title" class="lo-h">Ngại lắp đèn nội thất vì những điều này?</h2>
	<div class="lo-list">
		<?php foreach ( $skb_lo as $skb_l ) : ?>
		<div class="lo-card">
			<p class="lo-q"><?php echo esc_html( $skb_l[0] ); ?></p>
			<p class="lo-a"><?php echo esc_html( $skb_l[1] ); ?></p>
		</div>
		<?php endforeach; ?>
	</div>
</section>
	<?php
elseif ( 'so-lieu' === $skb_khoi ) :
	$skb_so = array(
		array( (int) wp_count_posts( 'mau_xe' )->publish, 'mẫu xe' ),
		array( count( skb_brands() ), 'hãng xe' ),
		array( (int) wp_count_posts( 'dai_ly' )->publish, 'đại lý lắp đặt' ),
		array( 24, 'tháng bảo hành' ),
	);
	?>
<section class="so" aria-label="SK Ambient Light qua các con số">
	<?php foreach ( $skb_so as $skb_s ) : if ( ! $skb_s[0] ) { continue; } ?>
	<div><b><?php echo esc_html( $skb_s[0] ); ?></b><span><?php echo esc_html( $skb_s[1] ); ?></span></div>
	<?php endforeach; ?>
</section>
	<?php
elseif ( 'hoi-dap' === $skb_khoi ) :
	$skb_hd = array(
		array( 'Lắp LED nội thất SK có phải cắt dây, khoan đục xe không?', 'Không. Sản phẩm là tấm ốp thay đúng vị trí ốp zin của từng dòng xe, đấu bằng giắc chờ, không cắt chích dây.', '', '' ),
		array( 'Giá trên web đã gồm công lắp đặt chưa?', 'Rồi. Giá ghi ở từng trang mẫu xe là giá đã gồm lắp đặt tại đại lý SK.', get_post_type_archive_link( 'mau_xe' ), 'Xem giá theo mẫu xe' ),
		array( 'Bảo hành bao lâu và tính từ khi nào?', 'Bảo hành 24 tháng, tính từ ngày bạn kích hoạt sản phẩm bằng mã dán trên hộp.', skb_activate_url(), 'Kích hoạt bảo hành' ),
		array( 'Điều khiển đèn bằng gì?', 'Bằng app trên điện thoại và nút bấm trên xe. Sau khi kích hoạt bảo hành, bạn nhận mã để dùng app.', home_url( '/huong-dan-su-dung/' ), 'Xem hướng dẫn sử dụng' ),
		array( 'Lắp ở đâu?', 'Tại hệ thống đại lý SK trên toàn quốc. Bạn đặt lịch trên web, hoặc gọi hotline để được chỉ điểm lắp gần nhất.', home_url( '/dat-lich/' ), 'Đặt lịch lắp đặt' ),
		array( 'Xe của tôi chưa có trong danh sách thì sao?', 'Bạn gọi hoặc nhắn Zalo ' . $skb_hotline . ', SK sẽ báo mẫu xe đó đã có hàng hay chưa.', '', '' ),
	);
	$skb_ld = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array(),
	);
	foreach ( $skb_hd as $skb_h ) {
		$skb_ld['mainEntity'][] = array(
			'@type'          => 'Question',
			'name'           => $skb_h[0],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $skb_h[1] ),
		);
	}
	?>
<section class="sec" aria-labelledby="hd-title">
	<div class="sec-head">
		<h2 id="hd-title">Câu hỏi thường gặp</h2>
	</div>
	<div class="hd-list">
		<?php foreach ( $skb_hd as $skb_i => $skb_h ) : ?>
		<details class="hd"<?php echo 0 === $skb_i ? ' open' : ''; ?>>
			<summary><?php echo esc_html( $skb_h[0] ); ?></summary>
			<div class="hd-a">
				<p><?php echo esc_html( $skb_h[1] ); ?></p>
				<?php if ( $skb_h[2] ) : ?><a href="<?php echo esc_url( $skb_h[2] ); ?>"><?php echo esc_html( $skb_h[3] ); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
			</div>
		</details>
		<?php endforeach; ?>
	</div>
	<script type="application/ld+json"><?php echo wp_json_encode( $skb_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore ?></script>
</section>
	<?php
elseif ( 'bai-viet' === $skb_khoi ) :
	$skb_hd_cat = get_term_by( 'slug', 'huong-dan-su-dung', 'category' );
	$skb_bv     = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'category__not_in'    => $skb_hd_cat ? array( (int) $skb_hd_cat->term_id ) : array(),
		)
	);
	if ( $skb_bv->have_posts() ) :
		$skb_tt_id = (int) get_option( 'page_for_posts' );
		?>
<section class="sec" aria-labelledby="bv-title">
	<div class="sec-head">
		<h2 id="bv-title">Bài viết mới</h2>
		<p><a href="<?php echo esc_url( $skb_tt_id ? get_permalink( $skb_tt_id ) : home_url( '/tin-tuc/' ) ); ?>">Xem tất cả →</a></p>
	</div>
	<div class="posts">
		<?php
		while ( $skb_bv->have_posts() ) :
			$skb_bv->the_post();
			$skb_chu  = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( get_the_content() ) ), -1, PREG_SPLIT_NO_EMPTY ) );
			$skb_phut = max( 1, (int) ceil( $skb_chu / 220 ) );
			?>
		<a class="post-card" href="<?php the_permalink(); ?>">
			<div class="thumb"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); } ?></div>
			<div class="body">
				<h3><?php the_title(); ?></h3>
				<p><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( $skb_phut ); ?> phút đọc</p>
			</div>
		</a>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>
</section>
		<?php
	endif;
endif;
