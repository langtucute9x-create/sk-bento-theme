<?php
/**
 * Bản đồ Việt Nam 3D có cột sáng tại các tỉnh có đại lý.
 * $args['filter'] = true: bấm điểm sẽ lọc danh sách ngay trên trang (trang Đại lý); mặc định mở trang Đại lý đã lọc.
 * Dữ liệu nền: Natural Earth (public domain) qua world-atlas (ISC).
 */
defined( 'ABSPATH' ) || exit;
$m = include get_template_directory() . '/inc/vn-map-data.php';
if ( ! is_array( $m ) ) {
	return;
}
$p      = $m['p'];
$cos    = cos( deg2rad( $p['tilt'] ) );
$rows   = skb_vn_dai_ly_theo_tinh();
$archive = get_post_type_archive_link( 'dai_ly' );
$filter = ! empty( $args['filter'] );
$f      = function ( $n ) { return round( $n, 1 ); };

$pins = array();
$mien = array( 'b' => 0, 't' => 0, 'n' => 0 );
$tong = 0;
foreach ( $rows as $ten => $r ) {
	list( $x, $y, $s ) = skb_vn_proj( $r[1], $r[2], $p );
	$pins[] = array( 'ten' => $ten, 'n' => $r[0], 'x' => $x, 'y' => $y, 's' => $s );
	$mien[ $r[3] ] += $r[0];
	$tong += $r[0];
}
usort( $pins, function ( $a, $b ) { return $a['y'] <=> $b['y']; } );
$max = $pins ? max( wp_list_pluck( $pins, 'n' ) ) : 1;

// Nhãn: tỉnh nhiều đại lý đặt trước, bỏ nhãn nếu chồng lên nhau (vẫn có chú thích khi chạm/rê chuột).
$fs     = 25;
$placed = array();
foreach ( array( 'hs', 'ts' ) as $sea ) {
	$placed[] = array( $m[ $sea ][0] - 80, $m[ $sea ][1] - 24, $m[ $sea ][0] + 80, $m[ $sea ][1] + 8 );
}
$labels = array();
$order  = $pins;
usort( $order, function ( $a, $b ) { return $b['n'] <=> $a['n'] ?: $a['y'] <=> $b['y']; } );
foreach ( $order as $pin ) {
	$h  = ( 70 + 90 * $pin['n'] / $max ) * $pin['s'];
	$ty = $pin['y'] - $h + 8;
	$w  = ( mb_strlen( $pin['ten'] ) + 3 ) * $fs * 0.56;
	foreach ( array( 'r', 'l' ) as $side ) {
		$x0  = 'r' === $side ? $pin['x'] + 14 : $pin['x'] - 14 - $w;
		$box = array( $x0 - 6, $ty - $fs, $x0 + $w + 6, $ty + 8 );
		if ( $box[0] < 4 || $box[2] > $m['vw'] - 4 ) {
			continue;
		}
		$hit = false;
		foreach ( $placed as $b ) {
			if ( $box[0] < $b[2] && $box[2] > $b[0] && $box[1] < $b[3] && $box[3] > $b[1] ) { $hit = true; break; }
		}
		if ( ! $hit ) {
			$placed[]                 = $box;
			$labels[ $pin['ten'] ] = array( 'r' === $side ? $pin['x'] + 14 : $pin['x'] - 14, $ty, 'r' === $side ? 'start' : 'end' );
			break;
		}
	}
}
$so_tinh = count( $pins );
$uid     = 'vn' . wp_rand( 100, 999 );
?>
<figure class="vnmap<?php echo $filter ? ' vnmap-filter' : ''; ?>" data-vnmap>
	<div class="vnmap-stage">
	<svg viewBox="0 0 <?php echo (int) $m['vw']; ?> <?php echo (int) $m['vh']; ?>" role="img" aria-labelledby="<?php echo esc_attr( $uid ); ?>-t">
		<title id="<?php echo esc_attr( $uid ); ?>-t"><?php echo esc_html( sprintf( 'Bản đồ đại lý SK Ambient Light: %d điểm lắp đặt tại %d tỉnh, thành', $tong, $so_tinh ) ); ?></title>
		<defs>
			<linearGradient id="<?php echo esc_attr( $uid ); ?>-fill" x1="0" y1="0" x2="0" y2="1">
				<stop offset="0" stop-color="#1A1030" stop-opacity=".55"/>
				<stop offset="1" stop-color="#3A1640" stop-opacity=".85"/>
			</linearGradient>
			<linearGradient id="<?php echo esc_attr( $uid ); ?>-dot" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="<?php echo (int) $m['vw']; ?>" y2="<?php echo (int) ( $m['vh'] / 2 ); ?>">
				<stop offset="0" stop-color="#4A3A6E"/>
				<stop offset=".42" stop-color="#4A3A6E"/>
				<stop offset=".5" stop-color="#FFB38A"/>
				<stop offset=".58" stop-color="#4A3A6E"/>
				<stop offset="1" stop-color="#4A3A6E"/>
				<animateTransform class="vn-sweep" attributeName="gradientTransform" type="translate" from="-<?php echo (int) $m['vw']; ?> 0" to="<?php echo (int) $m['vw']; ?> 0" dur="7s" repeatCount="indefinite"/>
			</linearGradient>
			<linearGradient id="<?php echo esc_attr( $uid ); ?>-beam" x1="0" y1="1" x2="0" y2="0">
				<stop offset="0" stop-color="#DF1C4E" stop-opacity=".95"/>
				<stop offset=".55" stop-color="#F49B3F" stop-opacity=".55"/>
				<stop offset="1" stop-color="#F49B3F" stop-opacity="0"/>
			</linearGradient>
			<radialGradient id="<?php echo esc_attr( $uid ); ?>-glow">
				<stop offset="0" stop-color="#fff"/>
				<stop offset=".35" stop-color="#FF7A59" stop-opacity=".9"/>
				<stop offset="1" stop-color="#DF1C4E" stop-opacity="0"/>
			</radialGradient>
			<filter id="<?php echo esc_attr( $uid ); ?>-blur" x="-10%" y="-10%" width="120%" height="120%"><feGaussianBlur stdDeviation="9"/></filter>
		</defs>
		<path class="vn-halo" d="<?php echo esc_attr( $m['outline'] ); ?>" transform="translate(0 14)" filter="url(#<?php echo esc_attr( $uid ); ?>-blur)"/>
		<?php for ( $z = 14; $z > 0; $z -= 2 ) : ?>
			<path class="vn-side" d="<?php echo esc_attr( $m['outline'] ); ?>" transform="translate(0 <?php echo (int) $z; ?>)"<?php echo 14 === $z ? ' style="stroke:#DF1C4E;stroke-opacity:.5"' : ''; ?>/>
		<?php endfor; ?>
		<path class="vn-land" d="<?php echo esc_attr( $m['outline'] ); ?>" fill="url(#<?php echo esc_attr( $uid ); ?>-fill)"/>
		<?php foreach ( $m['dots'] as $i => $d ) : ?>
			<path class="vn-dots" d="<?php echo esc_attr( $d ); ?>" stroke="url(#<?php echo esc_attr( $uid ); ?>-dot)" stroke-width="<?php echo esc_attr( (string) ( 3.4 + $i * 0.7 ) ); ?>"/>
		<?php endforeach; ?>
		<path class="vn-edge" d="<?php echo esc_attr( $m['outline'] ); ?>"/>
		<path class="vn-dots vn-isl" d="<?php echo esc_attr( $m['islands'] ); ?>" stroke-width="5"/>
		<text class="vn-sea" x="<?php echo esc_attr( (string) $m['hs'][0] ); ?>" y="<?php echo esc_attr( (string) $m['hs'][1] ); ?>" text-anchor="middle">QĐ. Hoàng Sa</text>
		<text class="vn-sea" x="<?php echo esc_attr( (string) $m['ts'][0] ); ?>" y="<?php echo esc_attr( (string) $m['ts'][1] ); ?>" text-anchor="middle">QĐ. Trường Sa</text>
		<?php
		foreach ( $pins as $i => $pin ) :
			$s  = $pin['s'];
			$h  = ( 70 + 90 * $pin['n'] / $max ) * $s;
			$x  = $f( $pin['x'] );
			$y  = $f( $pin['y'] );
			$bw = $f( 5 * $s );
			$label = sprintf( '%s: %d đại lý', $pin['ten'], $pin['n'] );
			?>
			<a class="vn-pin" href="<?php echo esc_url( add_query_arg( 'tinh', rawurlencode( $pin['ten'] ), $archive ) ); ?>" data-tinh="<?php echo esc_attr( $pin['ten'] ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" style="--d:<?php echo esc_attr( (string) ( ( $i % 6 ) * 0.4 ) ); ?>s">
				<title><?php echo esc_html( $label ); ?></title>
				<ellipse class="vn-ring" cx="<?php echo esc_attr( (string) $x ); ?>" cy="<?php echo esc_attr( (string) $y ); ?>" rx="<?php echo esc_attr( (string) $f( 15 * $s ) ); ?>" ry="<?php echo esc_attr( (string) $f( 15 * $s * $cos ) ); ?>"/>
				<ellipse class="vn-base" cx="<?php echo esc_attr( (string) $x ); ?>" cy="<?php echo esc_attr( (string) $y ); ?>" rx="<?php echo esc_attr( (string) $f( 10 * $s ) ); ?>" ry="<?php echo esc_attr( (string) $f( 10 * $s * $cos ) ); ?>"/>
				<rect class="vn-beam" x="<?php echo esc_attr( (string) $f( $pin['x'] - $bw / 2 ) ); ?>" y="<?php echo esc_attr( (string) $f( $pin['y'] - $h ) ); ?>" width="<?php echo esc_attr( (string) $bw ); ?>" height="<?php echo esc_attr( (string) $f( $h ) ); ?>" rx="<?php echo esc_attr( (string) $f( $bw / 2 ) ); ?>" fill="url(#<?php echo esc_attr( $uid ); ?>-beam)"/>
				<circle class="vn-top" cx="<?php echo esc_attr( (string) $x ); ?>" cy="<?php echo esc_attr( (string) $f( $pin['y'] - $h ) ); ?>" r="<?php echo esc_attr( (string) $f( 13 * $s ) ); ?>" fill="url(#<?php echo esc_attr( $uid ); ?>-glow)"/>
				<circle class="vn-core" cx="<?php echo esc_attr( (string) $x ); ?>" cy="<?php echo esc_attr( (string) $y ); ?>" r="<?php echo esc_attr( (string) $f( 4 * $s ) ); ?>"/>
				<circle class="vn-hit" cx="<?php echo esc_attr( (string) $x ); ?>" cy="<?php echo esc_attr( (string) $f( $pin['y'] - $h / 2 ) ); ?>" r="<?php echo esc_attr( (string) $f( max( 26, $h / 2 ) ) ); ?>"/>
				<?php if ( isset( $labels[ $pin['ten'] ] ) ) : $l = $labels[ $pin['ten'] ]; ?>
					<text class="vn-lab" x="<?php echo esc_attr( (string) $f( $l[0] ) ); ?>" y="<?php echo esc_attr( (string) $f( $l[1] ) ); ?>" text-anchor="<?php echo esc_attr( $l[2] ); ?>"><?php echo esc_html( $pin['ten'] ); ?> <tspan><?php echo esc_html( (string) $pin['n'] ); ?></tspan></text>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</svg>
	</div>
	<figcaption class="vnmap-stats">
		<span><b><?php echo esc_html( (string) $tong ); ?></b> điểm lắp đặt</span>
		<span><b><?php echo esc_html( (string) $so_tinh ); ?></b> tỉnh, thành</span>
		<span class="vn-mien">Bắc <b><?php echo esc_html( (string) $mien['b'] ); ?></b> · Trung <b><?php echo esc_html( (string) $mien['t'] ); ?></b> · Nam <b><?php echo esc_html( (string) $mien['n'] ); ?></b></span>
		<?php if ( $pins ) : // Chọn tỉnh bằng nút: trên điện thoại các cột sáng phía Bắc / phía Nam đứng quá sát nhau để chạm cho đúng ?>
		<nav class="vn-chips" aria-label="Chọn tỉnh, thành có đại lý">
			<?php foreach ( $pins as $pin ) : ?>
				<a class="vn-chip" href="<?php echo esc_url( add_query_arg( 'tinh', rawurlencode( $pin['ten'] ), $archive ) ); ?>" data-tinh="<?php echo esc_attr( $pin['ten'] ); ?>"><?php echo esc_html( $pin['ten'] ); ?> <b><?php echo esc_html( (string) $pin['n'] ); ?></b></a>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>
	</figcaption>
</figure>
