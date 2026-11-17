<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Register the block and its settings
class Gallery_Block {
    // Constructor to hook the necessary actions
    public function __construct() {
        add_action('init', [$this, 'register_block']);
        
    }

    // Register the block type
    public function register_block() {
        register_block_type('custom/gallery-block', [
            'editor_script' => 'gallery-block-js',
            'render_callback' => [$this, 'render_gallery_block'],
            'attributes' => [
                'galleryId' => [
                    'type' => 'integer',
                    'default' => 0,
                ],
            ],
        ]);
    }

    

    // Render the gallery block on the frontend.
    public function render_gallery_block( $attributes ) {
        if ( empty( $attributes['galleryId'] ) ) {
            return '<p>' . esc_html__( 'No gallery selected.', 'spartan-gallery' ) . '</p>';
        }

        return Spartan_Gallery_Renderer::render( absint( $attributes['galleryId'] ) );
    }
}

// Initialize the block
new Gallery_Block();



