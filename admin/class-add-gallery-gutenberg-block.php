<?php

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

    

    // Render the gallery block on the frontend
    public function render_gallery_block($attributes) {
        if (empty($attributes['galleryId'])) {
            return '<p>No gallery selected.</p>';
        }

        // Query the gallery post based on the ID
        $gallery_id = $attributes['galleryId'];
        $gallery_post = get_post($gallery_id);
        

        // Check if the post exists and is of the 'gallery' post type
        if ($gallery_post && $gallery_post->post_type === 'spartan_gallery_type') {
            
			$shortcode_container = '';

			$shortcode_container .= '<div class="spartan-gallery-box">';

			$my_meta_options = get_post_meta( $gallery_id , 'image_layout_select', true ); // array

			$get_all_post_attachements = get_post_meta( $gallery_id , 'spartan_gallery_uploaded_images', true ); 
            
			

			// Loop through each post ID
			foreach (explode(',',$get_all_post_attachements) as $post_id) {
				// Retrieve the post object by ID
				$post = get_post($post_id);

				// Check if the post exists
				if ($post) {
					// Get the GUID of the post and push it into the array
					$post_guids[] = $post;
				}
			}

			$attachments = $post_guids;

		
			switch ( $my_meta_options ) {
				case 'value-1':
				$shortcode_container .= Freewall_Chaotic_Container::display_chaotic_html_container( $attachments, $gallery_id ); //galley images post id and gallery id
				break;
				case 'value-2':
				$shortcode_container .= Freewall_Random_Container::display_random_container( $attachments, $gallery_id );
				break;
				case 'value-3':
				$shortcode_container .= Freewall_Fixed_Img_Container::display_fixed_img_container( $attachments, $gallery_id );
				break;
				case 'value-4':
				$shortcode_container .= Freewall_Pinterest_Container::display_pinterest_container( $attachments, $gallery_id );
				break;
				case 'value-5':
				$shortcode_container .= Spartan_Gallery_Hexagon::hexagon_html_container( $attachments, $gallery_id );
				break;
				case 'value-6':
				$shortcode_container .= ThreeD_Image_Slider::ThreeDImageSlidet_Html_container( $attachments, $gallery_id );
				break;
			}

			// end box
			$shortcode_container .= '</div>';

			//$CSSClass = new Spartan_Gallery_Generated_CSS_Output();
			//echo $CSSClass->output_php_Css($spartan_gallery_post_type['post_parent']);

			// return output
			return $shortcode_container;
        }

        return '<p>No images found in this gallery.</p>';
    }
}

// Initialize the block
new Gallery_Block();



