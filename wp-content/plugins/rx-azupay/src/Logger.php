<?php
/**
 * WooCommerce-logger wrapper for the AzuPay integration.
 *
 * Errors are always logged; debug lines only when "Debug log" is ticked
 * in the gateway settings. Logs appear under WooCommerce → Status → Logs,
 * source "rx-azupay". Never log the secret key or webhook tokens.
 *
 * @package RX\AzuPay
 */

declare(strict_types=1);

namespace RX\AzuPay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static logging helpers.
 */
final class Logger {

	private const SOURCE = 'rx-azupay';

	/**
	 * Log an error.
	 *
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Extra context.
	 */
	public static function error( string $message, array $context = array() ): void {
		wc_get_logger()->error( $message, array( 'source' => self::SOURCE ) + $context );
	}

	/**
	 * Log a notable event (payment confirmed, order cancelled, ...).
	 *
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Extra context.
	 */
	public static function info( string $message, array $context = array() ): void {
		wc_get_logger()->info( $message, array( 'source' => self::SOURCE ) + $context );
	}

	/**
	 * Log a debug line, only when the gateway's debug setting is on.
	 *
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Extra context.
	 */
	public static function debug( string $message, array $context = array() ): void {
		$settings = get_option( 'woocommerce_' . PayIdGateway::ID . '_settings', array() );
		if ( ! is_array( $settings ) || 'yes' !== ( $settings['debug'] ?? 'no' ) ) {
			return;
		}

		wc_get_logger()->debug( $message, array( 'source' => self::SOURCE ) + $context );
	}
}
