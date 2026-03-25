<?php
/**
 * Plugin Name: AI Provider for llama.cpp
 * Plugin URI: https://github.com/acrossWP/ai-provider-for-llamacpp
 * Description: AI Provider for llama.cpp for the WordPress AI Client.
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Version: 0.0.1
 * Author: AcrossWP
 * Author URI: https://acrosswp.com/
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: ai-provider-for-llamacpp
 *
 * @package WordPress\LlamaCppAiProvider
 */

declare(strict_types=1);

namespace WordPress\LlamaCppAiProvider;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

define( 'AI_PROVIDER_FOR_LLAMACPP_MIN_PHP_VERSION', '7.4' );
define( 'AI_PROVIDER_FOR_LLAMACPP_MIN_WP_VERSION', '6.9' );
define( 'AI_PROVIDER_FOR_LLAMACPP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_PROVIDER_FOR_LLAMACPP_PLUGIN_FILE', __FILE__ );

require_once __DIR__ . '/src/autoload.php';

/**
 * Displays an admin notice for requirement failures.
 *
 * @since 0.0.1
 *
 * @param string $message The error message to display.
 */
function requirement_notice( string $message ): void {
	if ( ! is_admin() ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p><?php echo wp_kses_post( $message ); ?></p>
	</div>
	<?php
}

/**
 * Checks if the PHP version meets the minimum requirement.
 *
 * @since 0.0.1
 *
 * @return bool
 */
function check_php_version(): bool {
	if ( version_compare( phpversion(), AI_PROVIDER_FOR_LLAMACPP_MIN_PHP_VERSION, '<' ) ) {
		add_action(
			'admin_notices',
			static function (): void {
				requirement_notice(
					sprintf(
						/* translators: 1: Required PHP version, 2: Current PHP version */
						__( 'The llama.cpp Provider plugin requires PHP version %1$s or higher. You are running PHP version %2$s.', 'ai-provider-for-llamacpp' ),
						AI_PROVIDER_FOR_LLAMACPP_MIN_PHP_VERSION,
						PHP_VERSION
					)
				);
			}
		);
		return false;
	}
	return true;
}

/**
 * Checks if the WordPress version meets the minimum requirement.
 *
 * @since 0.0.1
 *
 * @global string $wp_version WordPress version.
 *
 * @return bool
 */
function check_wp_version(): bool {
	if ( ! is_wp_version_compatible( AI_PROVIDER_FOR_LLAMACPP_MIN_WP_VERSION ) ) {
		add_action(
			'admin_notices',
			static function (): void {
				global $wp_version;
				requirement_notice(
					sprintf(
						/* translators: 1: Required WordPress version, 2: Current WordPress version */
						__( 'The llama.cpp Provider plugin requires WordPress version %1$s or higher. You are running WordPress version %2$s.', 'ai-provider-for-llamacpp' ),
						AI_PROVIDER_FOR_LLAMACPP_MIN_WP_VERSION,
						$wp_version
					)
				);
			}
		);
		return false;
	}
	return true;
}

/**
 * Loads the llama.cpp provider plugin.
 *
 * @since 0.0.1
 */
function load(): void {
	static $loaded = false;

	if ( $loaded ) {
		return;
	}

	$loaded = true;

	if ( ! check_php_version() || ! check_wp_version() ) {
		return;
	}

	$plugin = new Plugin();
	$plugin->init();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\load' );
