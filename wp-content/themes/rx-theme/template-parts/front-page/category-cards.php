<?php
/**
 * Homepage "Category Cards" section (Figma: "Section - 2. CATEGORY
 * CARDS SECTION (Strictly: Men, Women, Unisex)").
 *
 * The three cards are pulled live from the Men/Women/Unisex WooCommerce
 * product categories — name, product count, and link all come straight
 * from the taxonomy, not from separate hardcoded/Customizer content.
 * Per-category extras (the homepage image, "Shop label", and "Badge
 * label" — grammar/copy a plain category name can't give us) are
 * edited on the category itself under Products > Categories — see
 * inc/taxonomy-fields.php.
 *
 * Section eyebrow/heading/description are Customizer fields (see
 * rx_theme_category_cards_fields() in inc/customizer.php), same
 * pattern as the Hero section.
 *
 * Structure/colours verified 2026-09-21 against a full-resolution crop
 * of the cached home.png export plus pixel-sampled hex values (Figma's
 * API was rate-limited — see PROJECT.md §13 — so this used the
 * already-downloaded screenshot instead of guessing). Cards are
 * full-bleed photo with a badge overlay (top-left) and a bottom
 * gradient text block — not a separate photo-then-body layout, which
 * is what an earlier pass here assumed before checking.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Figma explicitly scopes this section to exactly these three
// categories, in this order — not "however many categories exist".
$rx_theme_category_slugs = array( 'men', 'women', 'unisex' );

$rx_theme_category_terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'slug'       => $rx_theme_category_slugs,
		'hide_empty' => false,
	)
);

if ( is_wp_error( $rx_theme_category_terms ) || empty( $rx_theme_category_terms ) ) {
	return;
}

// get_terms() with an array of slugs doesn't guarantee result order
// matches the input array, so re-sort into the fixed Men/Women/Unisex
// order ourselves.
$rx_theme_category_by_slug = array();
foreach ( $rx_theme_category_terms as $rx_theme_term ) {
	$rx_theme_category_by_slug[ $rx_theme_term->slug ] = $rx_theme_term;
}

$rx_theme_ordered_categories = array();
foreach ( $rx_theme_category_slugs as $rx_theme_slug ) {
	if ( isset( $rx_theme_category_by_slug[ $rx_theme_slug ] ) ) {
		$rx_theme_ordered_categories[] = $rx_theme_category_by_slug[ $rx_theme_slug ];
	}
}

if ( empty( $rx_theme_ordered_categories ) ) {
	return;
}
?>
<section class="rx-category-cards">
	<header class="rx-category-cards__header">
		<div class="rx-category-cards__heading-group">
			<p class="rx-eyebrow" data-customize-partial="rx_category_cards_eyebrow">
				<?php echo esc_html( rx_theme_get_mod( 'rx_category_cards_eyebrow' ) ); ?>
			</p>
			<h2 class="rx-section-heading" data-customize-partial="rx_category_cards_heading">
				<?php echo esc_html( rx_theme_get_mod( 'rx_category_cards_heading' ) ); ?>
			</h2>
		</div>
		<p class="rx-category-cards__description" data-customize-partial="rx_category_cards_description">
			<?php echo esc_html( rx_theme_get_mod( 'rx_category_cards_description' ) ); ?>
		</p>
	</header>

	<div class="rx-category-cards__grid">
		<?php foreach ( $rx_theme_ordered_categories as $rx_theme_category ) : ?>
			<?php
			$rx_theme_thumb_id  = rx_theme_category_thumbnail_id( $rx_theme_category );
			$rx_theme_shop_lbl  = rx_theme_category_shop_label( $rx_theme_category );
			$rx_theme_badge_lbl = rx_theme_category_badge_label( $rx_theme_category );
			$rx_theme_link      = get_term_link( $rx_theme_category );
			if ( is_wp_error( $rx_theme_link ) ) {
				continue;
			}
			?>
			<a class="rx-category-card" href="<?php echo esc_url( $rx_theme_link ); ?>" data-category="<?php echo esc_attr( $rx_theme_category->slug ); ?>">
				<?php if ( $rx_theme_thumb_id ) : ?>
					<?php echo wp_get_attachment_image( $rx_theme_thumb_id, 'large', false, array( 'class' => 'rx-category-card__image' ) ); ?>
				<?php endif; ?>

				<span class="rx-category-card__badge"><?php echo esc_html( $rx_theme_badge_lbl ); ?></span>

				<span class="rx-category-card__overlay">
					<span class="rx-category-card__name"><?php echo esc_html( $rx_theme_category->name ); ?></span>
					<span class="rx-category-card__meta">
						<?php
						printf(
							/* translators: 1: category shop label (e.g. "Men's"), 2: product count, 3: "Style" or "Styles". */
							esc_html__( 'Shop %1$s Shoes (%2$s %3$s)', 'rx-theme' ),
							esc_html( $rx_theme_shop_lbl ),
							esc_html( number_format_i18n( $rx_theme_category->count ) ),
							esc_html( _n( 'Style', 'Styles', $rx_theme_category->count, 'rx-theme' ) )
						);
						?>
					</span>
					<span class="rx-category-card__cta">
						<?php
						printf(
							/* translators: %s: category shop label. */
							esc_html__( 'Explore All %s', 'rx-theme' ),
							esc_html( $rx_theme_shop_lbl )
						);
						?>
					</span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
