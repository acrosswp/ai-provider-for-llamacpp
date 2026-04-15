<?php
/**
 * Settings class for the AI Provider for llama.cpp plugin.
 *
 * @since 0.0.1
 *
 * @package AcrossWP\AiProviderForLlamaCpp
 */

declare(strict_types=1);

namespace AcrossWP\AiProviderForLlamaCpp\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for the llama.cpp settings in the AcrossWP admin.
 *
 * Provides a settings page under Settings > llama.cpp for configuring the
 * server base URL.
 *
 * @since 0.0.1
 */
class LlamaCppSettings {

	private const DEFAULT_BASE_URL = 'http://127.0.0.1:8080';
	private const OPTION_GROUP     = 'aipf_llamacpp_settings_group';
	private const OPTION_NAME      = 'aipf_llamacpp_settings';
	private const PAGE_SLUG        = 'aipf-llamacpp';
	private const SECTION_ID       = 'ai_provider_for_llamacpp_main';

	/**
	 * Initializes the settings.
	 *
	 * @since 0.0.1
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_screen' ) );
	}

	/**
	 * Registers the setting and settings fields.
	 *
	 * @since 0.0.1
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			self::SECTION_ID,
			'',
			'__return_empty_string',
			self::PAGE_SLUG
		);

		add_settings_field(
			self::OPTION_NAME . '_base_url',
			__( 'Server URL', 'ai-provider-for-llamacpp' ),
			array( $this, 'render_base_url_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID,
			array( 'label_for' => self::OPTION_NAME . '-base-url' )
		);
	}

	/**
	 * Registers the settings screen under the Settings menu.
	 *
	 * @since 0.0.1
	 */
	public function register_settings_screen(): void {
		add_options_page(
			__( 'llama.cpp Settings', 'ai-provider-for-llamacpp' ),
			__( 'llama.cpp', 'ai-provider-for-llamacpp' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_screen' )
		);
	}

	/**
	 * Sanitizes the settings array.
	 *
	 * @since 0.0.1
	 *
	 * @param mixed $value The raw input value.
	 * @return array<string, string>
	 */
	public function sanitize_settings( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$base_url = isset( $value['base_url'] ) ? trim( (string) $value['base_url'] ) : '';
		if ( '' !== $base_url ) {
			$base_url = rtrim( esc_url_raw( $base_url ), '/' );
		}

		return array(
			'base_url' => $base_url,
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @since 0.0.1
	 */
	public function render_screen(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap" style="max-width: 50rem;">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>
				<?php
				printf(
					/* translators: 1: default URL in code tags, 2: opening link tag, 3: closing link tag */
					esc_html__( 'Configure the connection to your llama.cpp server. Leave the URL empty to use the default (%1$s). The provider itself appears on the %2$sSettings > Connectors%3$s screen.', 'ai-provider-for-llamacpp' ),
					'<code>' . esc_html( self::DEFAULT_BASE_URL ) . '</code>',
					'<a href="' . esc_url( admin_url( 'options-connectors.php' ) ) . '">',
					'</a>'
				);
				?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the server URL field.
	 *
	 * @since 0.0.1
	 */
	public function render_base_url_field(): void {
		$settings = self::get_settings();
		$value    = $settings['base_url'] ?? '';
		?>
		<input
			type="url"
			id="<?php echo esc_attr( self::OPTION_NAME . '-base-url' ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . '[base_url]' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="<?php echo esc_attr( self::DEFAULT_BASE_URL ); ?>"
		/>
		<p class="description">
			<?php
			printf(
				/* translators: 1: opening code tag, 2: closing code tag */
				esc_html__( 'The base URL of your llama.cpp server. Example: %1$shttp://127.0.0.1:8080%2$s', 'ai-provider-for-llamacpp' ),
				'<code>',
				'</code>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Returns the stored settings array.
	 *
	 * @since 0.0.1
	 *
	 * @return array<string, string>
	 */
	public static function get_settings(): array {
		return (array) get_option( self::OPTION_NAME, array() );
	}

	/**
	 * Returns the configured base URL, falling back to the default.
	 *
	 * @since 0.0.1
	 *
	 * @return string
	 */
	public static function get_base_url(): string {
		$settings = self::get_settings();
		if ( isset( $settings['base_url'] ) && '' !== $settings['base_url'] ) {
			return $settings['base_url'];
		}

		return self::DEFAULT_BASE_URL;
	}
}
