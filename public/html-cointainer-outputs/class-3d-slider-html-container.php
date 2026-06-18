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
        $image_options_textbox_height_range  = absint( get_post_meta( $gallery_id, 'textbox_height_range', true ) );
        $image_options_display_text          = get_post_meta( $gallery_id, 'display_text', true );
        $image_options_info_background_color = get_post_meta( $gallery_id, 'info_background_color', true );
        $image_options_container_font_size   = absint( get_post_meta( $gallery_id, 'container_font_size', true ) );
        $image_options_thumbnail_size        = get_post_meta( $gallery_id, 'thumbnail_size', true );
        $image_options_image_width           = absint( get_post_meta( $gallery_id, 'image_width', true ) );
        $image_options_image_height          = absint( get_post_meta( $gallery_id, 'image_height', true ) );
        $image_options_font_family           = get_post_meta( $gallery_id, 'font_family', true );
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
                    $output .= '<img style="height:' . $image_options_image_height . $image_measure_value . '; width:' . $image_options_image_width . $image_measure_value . ';" src="' . esc_url( $attachment_val->guid ) . '" />';
                } elseif ( $image_options_thumbnail_options == true and $image_options_display_original == false ) {
                    $output .= '<img style="height:' . $image_options_image_height . $image_measure_value . '; width:' . $image_options_image_width . $image_measure_value . ';" src="' . esc_url( $image_attributes[0] ) . '" />';
                } else {
                    $output .= '<p>' . esc_html__( 'Image Options: "Display Original" or "Display Thumbnail" must be enabled, but not both simultaneously.', 'spartan-gallery' ) . '</p>';
                }

                $output .= '<div style="color: black;" class="w-responsivGallery-info">';

                if ( ! empty( $attachments_id ) && true == $image_options_display_text ) {
                    $output .= '<div class="info">';
                    if ( ! empty( $attachment_val->post_title ) ) {
                        $output .= '<h5 class="text spartan-gallery-img-title">' . esc_html( $attachment_val->post_title ) . '</h5>';
                    }
                    if ( ! empty( $attachment_val->post_content ) ) {
                        $output .= '<p class="text spartan-gallery-img-description">' . wp_kses_post( $attachment_val->post_content ) . '</p>';
                    }
                    if ( ! empty( $attachment_val->guid ) ) {
                        $output .= '<td colspan="3" style="background-color:#005673; text-align:right; padding: 4px 0px;">
                            <button class="spartan-gallery-img-button text" onclick="parent.open(\'' . esc_js( esc_url( $attachment_val->guid ) ) . '\')">' . esc_html( $attachment_val->post_title ) . '</button></td>';
                    }
                    $output .= '</div>';
                }

                $output .= '</div>';
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
