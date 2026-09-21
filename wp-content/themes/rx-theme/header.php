<?php
/**
 * @package RX_Theme
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'rx-theme' ); ?></a>

<header id="site-header" class="rx-header">
	<?php // Global header — built against Figma in Milestone 3. ?>
</header>
