<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

Class ThreeD_Image_Slider
{
    static function ThreeDImageSlidet_Html_container( $attachments, $gallery_id )
    {
        $CSSClass = new Spartan_Gallery_Generated_CSS_Output();

        $image_options_add_extra_css         = get_post_meta( $gallery_id, 'add_extra_css', true );
        $image_options_display_original      = get_post_meta( $gallery_id, 'display_original', true );
        $image_options_thumbnail_options     = get_post_meta( $gallery_id, 'thumbnail_options', true );
        $image_options_thumbnail_size        = get_post_meta( $gallery_id, 'thumbnail_size', true );
        $image_options_image_width           = absint( get_post_meta( $gallery_id, 'image_width', true ) );
        $image_options_image_height          = absint( get_post_meta( $gallery_id, 'image_height', true ) );
        $image_options_image_measure_value   = get_post_meta( $gallery_id, 'image_measure_value', true );
        $safe_extra_css                      = wp_strip_all_tags( $image_options_add_extra_css );

        $allowed_measure_values = array( 'px', '%', 'em', 'rem', 'vw', 'vh' );
        $image_measure_value    = in_array( $image_options_image_measure_value, $allowed_measure_values, true )
            ? $image_options_image_measure_value
            : 'px';

        $output = '';
        $output .= '<style>' . $safe_extra_css . '</style>';
        $output .= '<div style="' . $CSSClass->output_php_Css( $gallery_id ) . '" class="spartan-gallery-wrapper w-gallery">';
        $output .= '<article id="demo-carousel" class="demo">';
        $output .= '<div id="carousel">';
        $output .= '<ul class="flip-items">';

        if ( isset( $attachments ) && ! empty( $attachments ) ) {
            foreach ( $attachments as $attachments_id => $attachment_val ) {

                $image_attributes = wp_get_attachment_image_src( $attachment_val->ID, $image_options_thumbnail_size );

                $output .= '<li href="' . esc_url( $attachment_val->guid ) . '" class="js-img-viwer" data-caption="" data-id="' . absint( $attachments_id ) . '">';

                if ( $image_options_display_original == true and $image_options_thumbnail_options == false ) {
                    $output .= '<img style="height:' . $image_options_image_height . $image_measure_value . '; width:' . $image_options_image_width . $image_measure_value . '; object-fit:cover; object-position:center;" src="' . esc_url( $attachment_val->guid ) . '" />';
                } elseif ( $image_options_thumbnail_options == true and $image_options_display_original == false ) {
                    $output .= '<img style="height:' . $image_options_image_height . $image_measure_value . '; width:' . $image_options_image_width . $image_measure_value . '; object-fit:cover; object-position:center;" src="' . esc_url( $image_attributes[0] ) . '" />';
                } else {
                    $output .= '<p>' . esc_html__( 'Image Options: "Display Original" or "Display Thumbnail" must be enabled, but not both simultaneously.', 'spartan-gallery' ) . '</p>';
                }

                $output .= '</li>';
            }
        }

        $output .= '</ul>';
        $output .= '</div>';
        $output .= '</article>';
        $output .= '</div>';

        $output .= '
        <script>
        jQuery( document ).ready( function( $ ){
            var carousel = $("#carousel").flipster({
                style: "carousel",
                spacing: -0.2,
                buttons: true,
            });
        });
        </script>';

        return $output;
    }
}
