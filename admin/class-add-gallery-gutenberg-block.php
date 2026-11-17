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

    // Register the block types
    public function register_block() {
        register_block_type('custom/album-block', [
            'editor_script' => 'gallery-block-js',
            'render_callback' => [$this, 'render_album_block'],
            'attributes' => [
                'galleryIds' => [
                    'type' => 'array',
                    'items' => [ 'type' => 'integer' ],
                    'default' => [],
                ],
                'columns' => [
                    'type' => 'integer',
                    'default' => 3,
                ],
                'showTitles' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
            ],
        ]);

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

    // Render the album block on the frontend. No galleries chosen = all galleries.
    public function render_album_block( $attributes ) {
        $ids = isset( $attributes['galleryIds'] ) && is_array( $attributes['galleryIds'] ) ? array_filter( array_map( 'absint', $attributes['galleryIds'] ) ) : array();

        return Spartan_Gallery_Renderer::render_album(
            array_values( $ids ),
            array(
                'columns' => isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3,
                'titles'  => ! isset( $attributes['showTitles'] ) || (bool) $attributes['showTitles'],
            )
        );
    }
}

// Initialize the block
new Gallery_Block();



