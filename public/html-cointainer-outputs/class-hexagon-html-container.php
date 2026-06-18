<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

Class Spartan_Gallery_Hexagon
{
    static function hexagon_html_container( $attachments, $gallery_id )
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
        $image_options_font_family           = get_post_meta( $gallery_id, 'font_family', true );
        $safe_extra_css                      = wp_strip_all_tags( $image_options_add_extra_css );

        $output = '';
        $output .= '<style>' . $safe_extra_css . '</style>';
        $output .= '<div style="' . $CSSClass->output_php_Css( $gallery_id ) . '" class="spartan-gallery-wrapper" style="position: relative; width: 100%;">';
        $output .= '<div class="masonary">';
        $output .= '<ul>';

        foreach ( $attachments as $attachments_id => $attachments_val ) {

            $image_attributes = wp_get_attachment_image_src( $attachments_val->ID, $image_options_thumbnail_size );

            $output .= '<li class="hex">';
            $output .= '<a class="js-img-viwer hexIn" data-caption="Title" data-id="' . absint( $attachments_id ) . '" href="' . esc_url( $attachments_val->guid ) . '">';

            if ( $image_options_display_original == true and $image_options_thumbnail_options == false ) {
                $output .= '<img style="height:100%; width:100%;" src="' . esc_url( $attachments_val->guid ) . '" />';
            } elseif ( $image_options_thumbnail_options == true and $image_options_display_original == false ) {
                $output .= '<img style="height:100%; width:100%;" src="' . esc_url( $image_attributes[0] ) . '" />';
            } else {
                $output .= '<p>' . esc_html__( 'Image Options: "Display Original" or "Display Thumbnail" must be enabled, but not both simultaneously.', 'spartan-gallery' ) . '</p>';
            }

            if ( ! empty( $attachments_id ) && true == $image_options_display_text ) {
                $output .= '<div class="info">';
                if ( ! empty( $attachments_val->post_title ) ) {
                    $output .= '<h5 class="text spartan-gallery-img-title">' . esc_html( $attachments_val->post_title ) . '</h5>';
                }
                if ( ! empty( $attachments_val->post_content ) ) {
                    $output .= '<p class="text spartan-gallery-img-description">' . wp_kses_post( $attachments_val->post_content ) . '</p>';
                }
                if ( ! empty( $attachments_val->guid ) ) {
                    $output .= '<td colspan="3" style="background-color:#005673; text-align:right; padding: 4px 0px;">
                        <button class="spartan-gallery-img-button text" onclick="parent.open(\'' . esc_js( esc_url( $attachments_val->guid ) ) . '\')">' . esc_html( $attachments_val->post_title ) . '</button></td>';
                }
                $output .= '</div>';
            }

            $output .= '</a>';
            $output .= '</li>';
        }

        $output .= '</ul>';
        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }
}
