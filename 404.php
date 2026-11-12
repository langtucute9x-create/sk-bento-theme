<?php
get_header();
?>
<div class="page-wrap">
	<section class="page-card">
		<h1><?php skb_e( 'Không tìm thấy trang' ); ?></h1>
		<p class="tile-text" style="margin-bottom:20px"><?php skb_e( 'Trang bạn tìm có thể đã được chuyển chỗ.' ); ?></p>
		<a class="btn btn-light btn-lg" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php skb_e( 'Về trang chủ' ); ?></a>
	</section>
</div>
<?php
get_footer();
