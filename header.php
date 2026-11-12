<?php
/**
 * Đầu trang
 */
$skb_ngoai = function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0B0B0D">
<meta name="color-scheme" content="dark">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#noi-dung"><?php skb_e( 'Bỏ qua đến nội dung' ); ?></a>
<div class="wrap">
<header class="site-header">
	<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) . ' - ' . skb__( 'Trang chủ' ) ); ?>">
		<?php
		$logo_id = get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			echo wp_get_attachment_image( $logo_id, 'full', false, array( 'alt' => get_bloginfo( 'name' ) ) );
		} else {
			printf( '<img src="%s" alt="%s">', esc_url( get_template_directory_uri() . '/assets/img/logo-full-png-1.webp' ), esc_attr( get_bloginfo( 'name' ) ) );
		}
		?>
	</a>
	<nav class="main-nav" aria-label="<?php echo esc_attr( skb__( 'Menu chính' ) ); ?>">
		<?php
		if ( $skb_ngoai ) {
			skb_ngu_menu();
		} else {
			wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'depth' => 1, 'fallback_cb' => 'skb_fallback_menu' ) );
		}
		?>
	</nav>
	<?php skb_ngu_chon(); ?>
	<div class="hdr-cta">
		<?php if ( $skb_ngoai ) : ?>
			<a class="btn btn-light btn-wa" href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener"><?php echo skb_wa_icon( 16 ); // phpcs:ignore ?> <?php skb_e( 'Nhắn WhatsApp' ); ?></a>
		<?php else : ?>
			<a class="btn btn-dark" href="<?php echo esc_url( skb_activate_url() ); ?>">Kích hoạt sản phẩm</a>
			<a class="btn btn-light" href="<?php echo esc_url( home_url( '/dat-lich/' ) ); ?>">Đặt lịch</a>
		<?php endif; ?>
	</div>
	<button class="menu-toggle" type="button" aria-label="<?php echo esc_attr( skb__( 'Mở menu' ) ); ?>" aria-expanded="false" aria-controls="mobile-panel">
		<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="6" cy="6" r="1.5"/><circle cx="18" cy="6" r="1.5"/><circle cx="6" cy="18" r="1.5"/><circle cx="18" cy="18" r="1.5"/></svg>
	</button>
</header>
<nav id="mobile-panel" class="mobile-panel" aria-label="<?php echo esc_attr( skb__( 'Menu điện thoại' ) ); ?>">
	<?php
	if ( $skb_ngoai ) {
		skb_ngu_menu();
	} else {
		wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'depth' => 1, 'fallback_cb' => 'skb_fallback_menu' ) );
	}
	?>
</nav>
<main id="noi-dung">
