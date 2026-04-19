<?php
declare(strict_types=1);
namespace AcrossWP\AiProviderForLlamaCpp\Settings;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LlamaCppDocs {
    private const PARENT_SLUG = 'aipf-llamacpp';
    private const PAGE_SLUG   = 'aipf-llamacpp-docs';

    public function init(): void {
        add_action( 'admin_menu', array( $this, 'register_docs_screen' ) );
    }

    public function register_docs_screen(): void {
        add_submenu_page(
            self::PARENT_SLUG,
            __( 'Documentation', 'ai-provider-for-llamacpp' ),
            __( 'Documentation', 'ai-provider-for-llamacpp' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_screen' )
        );
    }

    public function render_screen(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $doc_file = dirname( __DIR__, 2 ) . '/docs/getting-started.html';
        if ( ! file_exists( $doc_file ) ) {
            echo '<div class="wrap"><h1>Documentation</h1><p>Documentation file not found.</p></div>';
            return;
        }
        echo '<div class="wrap">';
        echo file_get_contents( $doc_file );
        echo '</div>';
    }
}
