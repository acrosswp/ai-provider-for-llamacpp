<?php
/**
 * WP_List_Table subclass for displaying llama.cpp models.
 *
 * @since 0.0.2
 *
 * @package AcrossWP\AiProviderForLlamaCpp
 */

declare(strict_types=1);

namespace AcrossWP\AiProviderForLlamaCpp\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table that renders available models from a llama.cpp server.
 *
 * @since 0.0.2
 */
class ModelsListTable extends \WP_List_Table {

	/**
	 * Raw model rows to display.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $model_rows = array();

	/**
	 * Constructor.
	 *
	 * @param array<int, array<string, mixed>> $model_rows Pre-built rows.
	 */
	public function __construct( array $model_rows ) {
		parent::__construct(
			array(
				'singular' => __( 'model', 'ai-provider-for-llamacpp' ),
				'plural'   => __( 'models', 'ai-provider-for-llamacpp' ),
				'ajax'     => false,
			)
		);

		$this->model_rows = $model_rows;
	}

	/**
	 * Define table columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return array(
			'model_name'  => __( 'Model', 'ai-provider-for-llamacpp' ),
			'status'      => __( 'Status', 'ai-provider-for-llamacpp' ),
			'object_type' => __( 'Type', 'ai-provider-for-llamacpp' ),
			'owned_by'    => __( 'Owned By', 'ai-provider-for-llamacpp' ),
			'actions'     => __( 'Details', 'ai-provider-for-llamacpp' ),
		);
	}

	/**
	 * Prepare items for display.
	 */
	public function prepare_items(): void {
		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->items           = $this->model_rows;
	}

	/**
	 * Default column renderer.
	 *
	 * @param array<string, mixed> $item        Row data.
	 * @param string               $column_name Column key.
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		return esc_html( (string) ( $item[ $column_name ] ?? "\xe2\x80\x94" ) );
	}

	/**
	 * Render the model name column (bold).
	 *
	 * @param array<string, mixed> $item Row data.
	 * @return string
	 */
	protected function column_model_name( $item ): string {
		return '<strong>' . esc_html( (string) $item['model_name'] ) . '</strong>';
	}

	/**
	 * Render the actions column with a View Details button.
	 *
	 * @param array<string, mixed> $item Row data.
	 * @return string
	 */
	protected function column_actions( $item ): string {
		return sprintf(
			'<button type="button" class="button button-small aipf-view-details" data-model="%s">%s</button>',
			esc_attr( (string) $item['model_name'] ),
			esc_html__( 'View Details', 'ai-provider-for-llamacpp' )
		);
	}

	/**
	 * Message when there are no items.
	 */
	public function no_items(): void {
		esc_html_e( 'No models found on the server.', 'ai-provider-for-llamacpp' );
	}

	/**
	 * Remove bulk actions.
	 *
	 * @return array<string, string>
	 */
	protected function get_bulk_actions(): array {
		return array();
	}

	/**
	 * Disable the views/subsubsub navigation.
	 *
	 * @return array<string, string>
	 */
	protected function get_views(): array {
		return array();
	}

	/**
	 * Formats a large number into a human-readable short form.
	 *
	 * @param int $number The number to format.
	 * @return string Formatted string.
	 */
	public static function format_number_short( int $number ): string {
		if ( $number >= 1000000000 ) {
			$value = $number / 1000000000;
			return ( floor( $value * 10 ) == $value * 10 )
				? number_format( $value, 1 ) . 'B'
				: number_format( $value, 2 ) . 'B';
		}

		if ( $number >= 1000000 ) {
			$value = $number / 1000000;
			return ( floor( $value * 10 ) == $value * 10 )
				? number_format( $value, 1 ) . 'M'
				: number_format( $value, 2 ) . 'M';
		}

		return number_format_i18n( $number );
	}
}
