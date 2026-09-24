<?php
/**
 * PHPStan bootstrap: the plugin constants are defined in rx-core.php,
 * which PHPStan never loads (it only analyses src/), so declare them
 * here for analysis. Values are placeholders — only the names matter.
 *
 * @package RX\Core
 */

define( 'RX_CORE_VERSION', '0.0.0' );
define( 'RX_CORE_FILE', __DIR__ . '/../rx-core.php' );
define( 'RX_CORE_DIR', dirname( __DIR__ ) );
define( 'RX_CORE_URL', '' );
