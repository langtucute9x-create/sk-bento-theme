<?php
/**
 * Lưới hãng xe
 */
$brands = skb_brands();
if ( ! $brands ) {
	echo '<p class="tile-text">Chưa có hãng xe nào. Thêm trong Mẫu xe &gt; Hãng xe.</p>';
	return;
}
?>
<div class="brands">
	<?php foreach ( $brands as $b ) : ?>
		<a class="brand" href="<?php echo esc_url( get_term_link( $b ) ); ?>">
			<b><?php echo esc_html( skb__( $b->name ) ); ?></b>
			<span><?php echo esc_html( $b->count ? sprintf( skb__( '%d mẫu' ), $b->count ) : skb__( 'Xem mẫu' ) ); ?></span>
		</a>
	<?php endforeach; ?>
</div>
