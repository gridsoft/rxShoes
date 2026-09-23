<?php
/**
 * Shop sidebar markup: Best for, Brands, Price range. Data and URLs come
 * from inc/shop-sidebar.php.
 *
 * On phones the sidebar is a "Filters" disclosure (closed unless a
 * filter is already on) so it doesn't push the products off-screen; on
 * desktop it sits open beside the grid.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_sidebar = rx_theme_shop_sidebar_data();
$rx_theme_price   = $rx_theme_sidebar['price'];

/**
 * Print one checkbox-style option list.
 *
 * @param array<int,array{label:string,count:int,active:bool,url:string}> $options Options.
 */
$rx_theme_options = static function ( array $options ): void {
	// Five rows show at once; a longer list scrolls inside the widget.
	printf( '<ul class="rx-widget__options%s">', count( $options ) > 5 ? ' rx-widget__options--scroll' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literals.

	foreach ( $options as $option ) {
		printf(
			'<li><a class="rx-option%1$s" href="%2$s" rel="nofollow"%3$s><span class="rx-option__box" aria-hidden="true"></span><span class="rx-option__label">%4$s</span><span class="rx-option__count">%5$d</span></a></li>',
			$option['active'] ? ' is-active' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal.
			esc_url( $option['url'] ),
			$option['active'] ? ' aria-current="true"' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal.
			esc_html( $option['label'] ),
			(int) $option['count']
		);
	}

	echo '</ul>';
};
?>
<details class="rx-shop-sidebar"<?php echo $rx_theme_sidebar['active'] > 0 ? ' open' : ''; ?>>
	<summary class="rx-shop-sidebar__toggle">
		<?php
		echo esc_html__( 'Filters', 'rx-theme' );

		if ( $rx_theme_sidebar['active'] > 0 ) {
			echo ' <span class="rx-shop-sidebar__badge">' . (int) $rx_theme_sidebar['active'] . '</span>';
		}
		?>
	</summary>

	<?php if ( $rx_theme_sidebar['best'] ) : ?>
		<section class="rx-widget rx-widget--best" aria-labelledby="rx-widget-best">
			<header class="rx-widget__header">
				<h2 class="rx-widget__title" id="rx-widget-best"><?php esc_html_e( 'Best for', 'rx-theme' ); ?></h2>
				<?php if ( array() !== rx_theme_shop_selected_slugs( 'rx_best' ) ) : ?>
					<a class="rx-widget__aside rx-widget__aside--link" href="<?php echo esc_url( rx_theme_shop_facet_reset_url( 'rx_best' ) ); ?>" rel="nofollow"><?php esc_html_e( 'Reset', 'rx-theme' ); ?></a>
				<?php endif; ?>
			</header>
			<p class="rx-widget__hint"><?php esc_html_e( 'Attribute indexing for training discipline', 'rx-theme' ); ?></p>
			<?php $rx_theme_options( $rx_theme_sidebar['best'] ); ?>
		</section>
	<?php endif; ?>

	<?php if ( $rx_theme_sidebar['brands'] ) : ?>
		<section class="rx-widget rx-widget--brands" aria-labelledby="rx-widget-brands">
			<header class="rx-widget__header">
				<h2 class="rx-widget__title" id="rx-widget-brands"><?php esc_html_e( 'Brands', 'rx-theme' ); ?></h2>
				<span class="rx-widget__aside">
					<?php
					printf(
						/* translators: %d: number of brands listed. */
						esc_html__( '%d Active', 'rx-theme' ),
						count( $rx_theme_sidebar['brands'] )
					);
					?>
				</span>
			</header>
			<?php $rx_theme_options( $rx_theme_sidebar['brands'] ); ?>
		</section>
	<?php endif; ?>

	<?php if ( $rx_theme_price ) : ?>
		<?php
		$rx_theme_span   = $rx_theme_price['max'] - $rx_theme_price['min'];
		$rx_theme_lo_pct = 100 * ( $rx_theme_price['from'] - $rx_theme_price['min'] ) / $rx_theme_span;
		$rx_theme_hi_pct = 100 * ( $rx_theme_price['to'] - $rx_theme_price['min'] ) / $rx_theme_span;
		$rx_theme_format = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) . '%s ' . get_woocommerce_currency();
		?>
		<section class="rx-widget rx-widget--price" aria-labelledby="rx-widget-price">
			<h2 class="rx-widget__title" id="rx-widget-price">
				<?php
				printf(
					/* translators: %s: currency code, e.g. "AUD". */
					esc_html__( 'Price range (%s)', 'rx-theme' ),
					esc_html( get_woocommerce_currency() )
				);
				?>
			</h2>

			<form class="rx-price" method="get" action="<?php echo esc_url( strtok( rx_theme_shop_url( array() ), '?' ) ); ?>" data-format="<?php echo esc_attr( $rx_theme_format ); ?>" style="--rx-lo:<?php echo esc_attr( round( $rx_theme_lo_pct, 2 ) ); ?>%;--rx-hi:<?php echo esc_attr( round( $rx_theme_hi_pct, 2 ) ); ?>%">
				<?php wc_query_string_form_fields( null, array( 'min_price', 'max_price', 'paged', 'product-page' ) ); ?>

				<div class="rx-price__values">
					<span data-rx-price-min><?php echo esc_html( rx_theme_format_money( (float) $rx_theme_price['from'], true ) ); ?></span>
					<span class="rx-price__max" data-rx-price-max><?php echo esc_html( rx_theme_format_money( (float) $rx_theme_price['to'], true ) ); ?></span>
				</div>

				<div class="rx-price__slider">
					<input type="range" name="min_price" min="<?php echo (int) $rx_theme_price['min']; ?>" max="<?php echo (int) $rx_theme_price['max']; ?>" step="1" value="<?php echo (int) $rx_theme_price['from']; ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'rx-theme' ); ?>">
					<input type="range" name="max_price" min="<?php echo (int) $rx_theme_price['min']; ?>" max="<?php echo (int) $rx_theme_price['max']; ?>" step="1" value="<?php echo (int) $rx_theme_price['to']; ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'rx-theme' ); ?>">
				</div>

				<div class="rx-price__captions">
					<span><?php esc_html_e( 'Entry tier', 'rx-theme' ); ?></span>
					<span><?php esc_html_e( 'Carbon/Lifter tier', 'rx-theme' ); ?></span>
				</div>

				<button class="rx-price__apply" type="submit"><?php esc_html_e( 'Apply', 'rx-theme' ); ?></button>
			</form>
		</section>
	<?php endif; ?>

	<aside class="rx-shop-note" aria-labelledby="rx-shop-note-title">
		<h2 class="rx-shop-note__title" id="rx-shop-note-title">
			<span class="rx-shop-note__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="14" height="14" focusable="false"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</span>
			<?php esc_html_e( 'Why build a rotation?', 'rx-theme' ); ?>
		</h2>
		<p class="rx-shop-note__text"><?php esc_html_e( 'Rotating lifting platforms with flexible sprint/wod trainers doubles midsole longevity and prevents achilles strain.', 'rx-theme' ); ?></p>
	</aside>
</details>
