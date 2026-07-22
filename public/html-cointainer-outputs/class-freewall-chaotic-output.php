<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

Class Freewall_Chaotic_Container
{
    static function display_chaotic_html_container( $attachments, $gallery_id )
    {
        $CSSClass = new Spartan_Gallery_Generated_CSS_Output();

        $image_options_add_extra_css         = get_post_meta( $gallery_id, 'add_extra_css', true );
        $image_options_display_original      = get_post_meta( $gallery_id, 'display_original', true );
        $image_options_img_margin            = absint( get_post_meta( $gallery_id, 'img_margin', true ) );
        $image_options_thumbnail_options     = get_post_meta( $gallery_id, 'thumbnail_options', true );
        $image_options_thumbnail_size        = get_post_meta( $gallery_id, 'thumbnail_size', true );
        $safe_extra_css                      = wp_strip_all_tags( $image_options_add_extra_css );

        $html = '';
        $html .= '<style>' . $safe_extra_css . '</style>';
        $html .= '<div style="' . $CSSClass->output_php_Css( $gallery_id ) . '" class="spartan-gallery-wrapper">';
        $html .= '<div id="freewall-chaotic" class="masonary">';

        $img_size = array( '120', '160', '180', '240', '280', '320' );

        foreach ( (array) $attachments as $attachment_id => $attachment_val ) {

            $image_attributes = wp_get_attachment_image_src( $attachment_val->ID, $image_options_thumbnail_size );

            if ( $image_options_display_original == true xor $image_options_thumbnail_options == true ) {
                $brick_w_index = array_rand( $img_size, 1 );
                $brick_h_index = wp_rand( max( 0, $brick_w_index - 1 ), min( count( $img_size ) - 1, $brick_w_index + 1 ) );
                $html .= '<div class="freewall-brick-chaotic brick" data-width="' . $img_size[ $brick_w_index ] . '" data-height="' . $img_size[ $brick_h_index ] . '" data-delay="' . absint( $attachment_id ) . '">';
            }

            $html .= '<a href="' . esc_url( $attachment_val->guid ) . '" class="js-img-viwer img-true-location" data-caption="" data-id="' . absint( $attachment_id ) . '">';

            if ( $image_options_display_original == true and $image_options_thumbnail_options == false ) {
                $html .= '<img class="image" style="height:100%; width:100%; object-fit:cover; object-position:center;" loading="lazy" src="' . esc_url( $attachment_val->guid ) . '" />';
            } elseif ( $image_options_thumbnail_options == true and $image_options_display_original == false ) {
                $html .= '<img class="image" style="height:100%; width:100%; object-fit:cover; object-position:center;" loading="lazy" src="' . esc_url( $image_attributes[0] ) . '" />';
            } else {
                return '<p>' . esc_html__( 'Image Options: "Display Original" or "Display Thumbnail" must be enabled, but not both simultaneously.', 'spartan-gallery' ) . '</p>';
            }

            $html .= '</a>';

            if ( $image_options_display_original == true xor $image_options_thumbnail_options == true ) {
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        $html .= '<script>
            jQuery( document ).ready( function( $ ){
                $(".freewall-brick-chaotic").each(function() {
                    var wall = new Freewall("#freewall-chaotic");
                    wall.reset({
                        animate: true,
                        cellW: 150,
                        cellH: 150,
                        gutterY: ' . $image_options_img_margin . ',
                        gutterX: ' . $image_options_img_margin . ',
                        onResize: function() {
                            wall.fitWidth();
                        }
                    });
                    wall.fitWidth();
                });
            });
        </script>';

        return $html;
    }
}
