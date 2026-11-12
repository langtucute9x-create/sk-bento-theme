<?php
/**
 * Thanh chọn hãng / dòng xe trên trang Hãng xe (cuộn ngang trên điện thoại).
 * Ý tưởng: Lucian UX/UI prompt #242 (MIT) — viết lại theo theme.
 */
$term = get_queried_object();
if ( ! $term instanceof WP_Term ) {
	return;
}
$brand  = $term->parent ? get_term( $term->parent, 'hang_xe' ) : $term;
$models = get_terms( array( 'taxonomy' => 'hang_xe', 'parent' => $brand->term_id, 'hide_empty' => true, 'orderby' => 'name' ) );
?>
<nav class="chips" aria-label="<?php echo esc_attr( skb__( 'Chọn hãng xe' ) ); ?>">
	<a class="chip" href="<?php echo esc_url( get_post_type_archive_link( 'mau_xe' ) ); ?>"><?php skb_e( 'Tất cả hãng' ); ?></a>
	<?php foreach ( skb_brands() as $b ) : ?>
		<a class="chip<?php echo $b->term_id === $brand->term_id ? ' on' : ''; ?>" href="<?php echo esc_url( get_term_link( $b ) ); ?>"<?php echo $b->term_id === $brand->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( skb__( $b->name ) ); ?></a>
	<?php endforeach; ?>
</nav>
<?php if ( ! is_wp_error( $models ) && $models ) : ?>
<nav class="chips chips-sub" aria-label="<?php echo esc_attr( sprintf( skb__( 'Chọn dòng xe %s' ), skb__( $brand->name ) ) ); ?>">
	<a class="chip<?php echo $term->term_id === $brand->term_id ? ' on' : ''; ?>" href="<?php echo esc_url( get_term_link( $brand ) ); ?>"<?php echo $term->term_id === $brand->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( sprintf( skb__( 'Tất cả %s' ), skb__( $brand->name ) ) ); ?></a>
	<?php foreach ( $models as $m ) : ?>
		<a class="chip<?php echo $m->term_id === $term->term_id ? ' on' : ''; ?>" href="<?php echo esc_url( get_term_link( $m ) ); ?>"<?php echo $m->term_id === $term->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $m->name ); ?> <span><?php echo (int) $m->count; ?></span></a>
	<?php endforeach; ?>
</nav>
<?php endif; ?>
