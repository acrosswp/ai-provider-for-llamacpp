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
		add_action( 'wp_ajax_aipf_llamacpp_model_details', array( $this, 'ajax_model_details' ) );
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
		<div class="wrap">
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

			<?php $this->render_models_section(); ?>
			<?php $this->render_getting_started_section(); ?>
		</div>
		<?php
	}

	/**
	 * Renders the available models section using WP_List_Table.
	 *
	 * @since 0.0.2
	 */
	private function render_models_section(): void {
		$base_url = self::get_base_url();
		$models   = $this->fetch_models_info( $base_url );

		?>
		<hr />
		<h2><?php esc_html_e( 'Available Models', 'ai-provider-for-llamacpp' ); ?></h2>
		<?php

		if ( is_wp_error( $models ) ) {
			?>
			<div class="notice notice-error inline">
				<p>
					<?php
					printf(
						/* translators: %s: error message */
						esc_html__( 'Could not connect to the llama.cpp server: %s', 'ai-provider-for-llamacpp' ),
						esc_html( $models->get_error_message() )
					);
					?>
				</p>
			</div>
			<?php
			return;
		}

		$rows = array();
		foreach ( $models as $model ) {
			$status_value = $model['status']['value'] ?? '';
			$rows[] = array(
				'model_name'  => $model['id'] ?? __( 'Unknown', 'ai-provider-for-llamacpp' ),
				'status'      => '' !== $status_value ? ucfirst( $status_value ) : '-',
				'object_type' => $model['object'] ?? 'model',
				'owned_by'    => $model['owned_by'] ?? '-',
			);
		}

		$table = new ModelsListTable( $rows );
		$table->prepare_items();
		$table->display();

		$this->render_model_details_modal();
	}

	/**
	 * Renders a "Getting Started Guide" link below the Available Models section.
	 *
	 * Links to the Markdown documentation on GitHub rather than embedding the
	 * HTML file inline so the page stays lean and the docs stay up to date.
	 */
	private function render_getting_started_section(): void {
		?>
		<hr style="margin: 2em 0;" />
		<p>
			<a href="https://github.com/acrosswp/ai-provider-for-llamacpp/blob/main/docs/getting-started.md" target="_blank" rel="noopener noreferrer" class="button">
				<?php esc_html_e( 'Getting Started Guide', 'ai-provider-for-llamacpp' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Renders the modal markup and inline JS/CSS for the model details popup.
	 */
	private function render_model_details_modal(): void {
		$nonce = wp_create_nonce( 'aipf_model_details' );
		?>
		<div id="aipf-model-modal" style="display:none;">
			<div class="aipf-modal-backdrop"></div>
			<div class="aipf-modal-wrap">
				<div class="aipf-modal-header">
					<h2 id="aipf-modal-title"></h2>
					<button type="button" class="aipf-modal-close" aria-label="<?php esc_attr_e( 'Close', 'ai-provider-for-llamacpp' ); ?>">&times;</button>
				</div>
				<div id="aipf-modal-body" class="aipf-modal-body">
					<span class="spinner is-active" style="float:none;margin:20px auto;display:block;"></span>
				</div>
			</div>
		</div>
		<style>
			.aipf-modal-backdrop {
				position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 100000;
			}
			.aipf-modal-wrap {
				position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%);
				background: #fff; border-radius: 8px; z-index: 100001;
				width: 560px; max-width: 90vw; max-height: 80vh;
				box-shadow: 0 4px 20px rgba(0,0,0,.3); display: flex; flex-direction: column;
			}
			.aipf-modal-header {
				display: flex; justify-content: space-between; align-items: center;
				padding: 16px 20px; border-bottom: 1px solid #ddd;
			}
			.aipf-modal-header h2 { margin: 0; font-size: 16px; }
			.aipf-modal-close {
				background: none; border: none; font-size: 24px; cursor: pointer;
				color: #666; line-height: 1; padding: 0 4px;
			}
			.aipf-modal-close:hover { color: #d63638; }
			.aipf-modal-body { padding: 20px; overflow-y: auto; }
			.aipf-modal-body table { width: 100%; border-collapse: collapse; }
			.aipf-modal-body th,
			.aipf-modal-body td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
			.aipf-modal-body th { width: 40%; color: #50575e; font-weight: 600; }
			.aipf-modal-body td { color: #1d2327; }
			.aipf-modal-body .aipf-error { color: #d63638; }
		</style>
		<script>
		(function() {
			var modal = document.getElementById('aipf-model-modal');
			var titleEl = document.getElementById('aipf-modal-title');
			var body  = document.getElementById('aipf-modal-body');
			var nonce = <?php echo wp_json_encode( $nonce ); ?>;

			function closeModal() { modal.style.display = 'none'; }

			modal.querySelector('.aipf-modal-backdrop').addEventListener('click', closeModal);
			modal.querySelector('.aipf-modal-close').addEventListener('click', closeModal);
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && modal.style.display !== 'none') closeModal();
			});

			document.addEventListener('click', function(e) {
				var btn = e.target.closest('.aipf-view-details');
				if (!btn) return;

				var modelId = btn.getAttribute('data-model');
				titleEl.textContent = modelId;
				body.innerHTML = '<span class="spinner is-active" style="float:none;margin:20px auto;display:block;"></span>';
				modal.style.display = '';

				var xhr = new XMLHttpRequest();
				xhr.open('POST', ajaxurl, true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onload = function() {
					if (xhr.status === 200) {
						try {
							var data = JSON.parse(xhr.responseText);
							if (data.success) {
								body.innerHTML = data.data.html;
							} else {
								body.innerHTML = '<p class="aipf-error">' + (data.data || 'Unknown error') + '</p>';
							}
						} catch(err) {
							body.innerHTML = '<p class="aipf-error">Invalid response from server.</p>';
						}
					} else {
						body.innerHTML = '<p class="aipf-error">Request failed (HTTP ' + xhr.status + ').</p>';
					}
				};
				xhr.onerror = function() {
					body.innerHTML = '<p class="aipf-error">Network error.</p>';
				};
				xhr.send('action=aipf_llamacpp_model_details&_wpnonce=' + encodeURIComponent(nonce) + '&model_id=' + encodeURIComponent(modelId));
			});
		})();
		</script>
		<?php
	}

	/**
	 * AJAX handler for fetching model details on demand.
	 *
	 * @since 0.0.3
	 */
	public function ajax_model_details(): void {
		check_ajax_referer( 'aipf_model_details' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'ai-provider-for-llamacpp' ) );
		}

		$model_id = isset( $_POST['model_id'] ) ? sanitize_text_field( wp_unslash( $_POST['model_id'] ) ) : '';
		if ( '' === $model_id ) {
			wp_send_json_error( __( 'No model specified.', 'ai-provider-for-llamacpp' ) );
		}

		$base_url = self::get_base_url();
		$models   = $this->fetch_models_info( $base_url );

		if ( is_wp_error( $models ) ) {
			wp_send_json_error( $models->get_error_message() );
		}

		// Find the requested model.
		$model = null;
		foreach ( $models as $m ) {
			if ( ( $m['id'] ?? '' ) === $model_id ) {
				$model = $m;
				break;
			}
		}

		if ( null === $model ) {
			wp_send_json_error( __( 'Model not found on the server.', 'ai-provider-for-llamacpp' ) );
		}

		$props = $this->fetch_props_info( $base_url );
		$is_router = ! empty( $props ) && ! is_wp_error( $props ) && ( $props['role'] ?? '' ) === 'router';

		$meta         = $model['meta'] ?? array();
		$status_value = $model['status']['value'] ?? '';
		$model_ctx    = 0;
		$model_slots  = 0;
		$is_remote    = false;

		if ( ! is_wp_error( $props ) && is_array( $props ) && ! $is_router ) {
			$model_ctx   = (int) ( $props['default_generation_settings']['params']['n_ctx'] ?? $props['default_generation_settings']['n_ctx'] ?? 0 );
			$model_slots = (int) ( $props['total_slots'] ?? 0 );
		}

		// In router mode, try to fetch full metadata.
		if ( $is_router && 'loaded' === $status_value ) {
			$args = $model['status']['args'] ?? array();

			// Try sub-server directly (works when server is on the same machine).
			$port = $this->extract_arg_value( $args, '--port' );
			if ( $port > 0 ) {
				$sub_base = 'http://127.0.0.1:' . $port;
				$sub_meta = $this->fetch_sub_model_meta( $sub_base );
				if ( ! empty( $sub_meta ) ) {
					$meta = $sub_meta;
				}
				$sub_props = $this->fetch_props_info( $sub_base );
				if ( ! is_wp_error( $sub_props ) && is_array( $sub_props ) ) {
					$model_ctx   = (int) ( $sub_props['default_generation_settings']['n_ctx'] ?? 0 );
					$model_slots = (int) ( $sub_props['total_slots'] ?? 0 );
				}
			}

			// Fallback: /props?model=X proxied through the router to the sub-server.
			if ( empty( $meta ) || 0 === $model_ctx ) {
				$props_url   = rtrim( $base_url, '/' ) . '/props?model=' . rawurlencode( $model_id );
				$model_props = $this->fetch_props_for_url( $props_url );
				if ( ! is_wp_error( $model_props ) && is_array( $model_props ) ) {
					if ( 0 === $model_ctx ) {
						$model_ctx = (int) ( $model_props['default_generation_settings']['n_ctx'] ?? 0 );
					}
					if ( 0 === $model_slots ) {
						$model_slots = (int) ( $model_props['total_slots'] ?? 0 );
					}
					$is_remote = true;
				}
			}

			// Fallback: /slots endpoint through the main server URL.
			if ( 0 === $model_ctx ) {
				$slots_data = $this->fetch_slots_info( $base_url, $model_id );
				if ( ! empty( $slots_data ) ) {
					$model_ctx   = (int) ( $slots_data[0]['n_ctx'] ?? 0 );
					$model_slots = count( $slots_data );
					$is_remote   = true;
				}
			}

			// Fallback: extract --ctx-size from args.
			if ( 0 === $model_ctx ) {
				$ctx_arg = $this->extract_arg_value( $args, '--ctx-size' );
				if ( $ctx_arg > 0 ) {
					$model_ctx = $ctx_arg;
				}
			}
		}

		$em   = '-';
		$rows = array(
			__( 'Runtime Context', 'ai-provider-for-llamacpp' )
				=> $model_ctx > 0
					? number_format_i18n( $model_ctx ) . ' ' . __( 'tokens', 'ai-provider-for-llamacpp' )
					: $em,
			__( 'Slots', 'ai-provider-for-llamacpp' )
				=> $model_slots > 0 ? (string) $model_slots : $em,
			__( 'Training Context', 'ai-provider-for-llamacpp' )
				=> ! empty( $meta['n_ctx_train'] )
					? number_format_i18n( (int) $meta['n_ctx_train'] ) . ' ' . __( 'tokens', 'ai-provider-for-llamacpp' )
					: $em,
			__( 'Parameters', 'ai-provider-for-llamacpp' )
				=> ! empty( $meta['n_params'] )
					? ModelsListTable::format_number_short( (int) $meta['n_params'] )
					: $em,
			__( 'Model Size', 'ai-provider-for-llamacpp' )
				=> ! empty( $meta['size'] )
					? size_format( (int) $meta['size'] )
					: $em,
			__( 'Embedding', 'ai-provider-for-llamacpp' )
				=> ! empty( $meta['n_embd'] )
					? number_format_i18n( (int) $meta['n_embd'] )
					: $em,
			__( 'Vocabulary', 'ai-provider-for-llamacpp' )
				=> ! empty( $meta['n_vocab'] )
					? number_format_i18n( (int) $meta['n_vocab'] )
					: $em,
		);

		ob_start();
		echo '<table>';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
		}
		echo '</table>';

		if ( $is_remote && empty( $meta ) ) {
			echo '<p style="margin-top:12px;padding:8px 12px;background:#f0f6fc;border-left:4px solid #2271b1;color:#1d2327;font-size:13px;">';
			echo esc_html__( 'Some details are only available when WordPress and the llama.cpp server are on the same machine. Remote servers (ngrok, VPS) expose limited metadata through the router.', 'ai-provider-for-llamacpp' );
			echo '</p>';
		}

		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Extracts a flag value from a model's status args array.
	 *
	 * @param array<int, string> $args Command-line args from status.
	 * @param string             $flag The flag to look for (e.g. '--port').
	 * @return int The integer value, or 0 if not found.
	 */
	private function extract_arg_value( array $args, string $flag ): int {
		foreach ( $args as $i => $arg ) {
			if ( $flag === $arg && isset( $args[ $i + 1 ] ) ) {
				return (int) $args[ $i + 1 ];
			}
		}
		return 0;
	}

	/**
	 * Fetches slot info from the /slots endpoint for a specific model.
	 *
	 * @param string $base_url The server base URL.
	 * @param string $model_id The model ID.
	 * @return array<int, array<string, mixed>> Slots data, or empty array.
	 */
	private function fetch_slots_info( string $base_url, string $model_id ): array {
		$url      = rtrim( $base_url, '/' ) . '/slots?model=' . rawurlencode( $model_id );
		$is_local = in_array( wp_parse_url( $url, PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 3,
				'sslverify' => ! $is_local,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $body ) ? $body : array();
	}

	/**
	 * Fetches model meta from a sub-server's /v1/models endpoint.
	 *
	 * @param string $base_url The sub-server base URL.
	 * @return array<string, mixed> Meta array, or empty if unavailable.
	 */
	private function fetch_sub_model_meta( string $base_url ): array {
		$url      = rtrim( $base_url, '/' ) . '/v1/models';
		$response = wp_remote_get( $url, array( 'timeout' => 3, 'sslverify' => false ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$data = $body['data'] ?? array();

		if ( ! empty( $data[0]['meta'] ) ) {
			return (array) $data[0]['meta'];
		}

		return array();
	}



	/**
	 * Fetches model information from the llama.cpp /v1/models endpoint.
	 *
	 * @since 0.0.2
	 *
	 * @param string $base_url The server base URL.
	 * @return array<int, array<string, mixed>>|\WP_Error List of model data or error.
	 */
	private function fetch_models_info( string $base_url ) {
		$url      = rtrim( $base_url, '/' ) . '/v1/models';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 5,
				'sslverify' => wp_parse_url( $url, PHP_URL_HOST ) !== '127.0.0.1',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error(
				'llamacpp_models_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Server returned HTTP %d', 'ai-provider-for-llamacpp' ),
					wp_remote_retrieve_response_code( $response )
				)
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return (array) ( $body['data'] ?? array() );
	}

	/**
	 * Fetches server properties from the llama.cpp /props endpoint.
	 *
	 * @since 0.0.2
	 *
	 * @param string $base_url The server base URL.
	 * @return array<string, mixed>|\WP_Error Props data or error.
	 */
	private function fetch_props_info( string $base_url ) {
		$url      = rtrim( $base_url, '/' ) . '/props';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 3,
				'sslverify' => wp_parse_url( $url, PHP_URL_HOST ) !== '127.0.0.1',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( 'llamacpp_props_error', 'Failed to fetch /props' );
		}

		return (array) json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Fetches properties from a fully-formed URL.
	 *
	 * @param string $url The full props URL (e.g. with ?model= param).
	 * @return array<string, mixed>|\WP_Error Props data or error.
	 */
	private function fetch_props_for_url( string $url ) {
		$is_local = in_array( wp_parse_url( $url, PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 3,
				'sslverify' => ! $is_local,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( 'llamacpp_props_error', 'Failed to fetch props' );
		}

		return (array) json_decode( wp_remote_retrieve_body( $response ), true );
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
