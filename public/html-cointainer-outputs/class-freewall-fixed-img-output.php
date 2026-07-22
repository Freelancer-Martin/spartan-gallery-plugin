<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

Class Freewall_Fixed_Img_Container
{
    static function display_fixed_img_container( $attachments, $gallery_id )
    {
        $CSSClass = new Spartan_Gallery_Generated_CSS_Output();

        $image_options_add_extra_css         = get_post_meta( $gallery_id, 'add_extra_css', true );
        $image_options_display_original      = get_post_meta( $gallery_id, 'display_original', true );
        $image_options_img_margin            = absint( get_post_meta( $gallery_id, 'img_margin', true ) );
        $image_options_thumbnail_options     = get_post_meta( $gallery_id, 'thumbnail_options', true );
        $image_options_thumbnail_size        = get_post_meta( $gallery_id, 'thumbnail_size', true );
        $image_options_image_width           = absint( get_post_meta( $gallery_id, 'image_width', true ) );
        $image_options_image_height          = absint( get_post_meta( $gallery_id, 'image_height', true ) );
        $safe_extra_css                      = wp_strip_all_tags( $image_options_add_extra_css );

        $html = '';
        $html .= '<style>' . $safe_extra_css . '</style>';
        $html .= '<div style="' . $CSSClass->output_php_Css( $gallery_id ) . '" class="spartan-gallery-wrapper">';
        $html .= '<div id="freewall" class="masonary">';

        foreach ( (array) $attachments as $attachment_id => $attachment_val ) {

            $image_attributes = wp_get_attachment_image_src( $attachment_val->ID, $image_options_thumbnail_size );

            if ( $image_options_display_original == true xor $image_options_thumbnail_options == true ) {
                $html .= '<div class="freewall-brick brick overlay" data-width="' . $image_options_image_width . '" data-height="' . $image_options_image_height . '" data-delay="' . absint( $attachment_id ) . '">';
            }

            $html .= '<a href="' . esc_url( $attachment_val->guid ) . '" class="js-img-viwer img-true-location container" data-caption="" data-id="' . absint( $attachment_id ) . '">';

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

        $html .= '<script>';
        $html .= 'jQuery( document ).ready( function( $ ){';
        $html .= '$(".freewall-brick").each(function() {';
        $html .= 'var wall = new Freewall("#freewall");';
        $html .= 'wall.reset({';
        $html .= 'animate: true,';
        $html .= 'gutterY: ' . $image_options_img_margin . ',';
        $html .= 'gutterX: ' . $image_options_img_margin . ',';
        $html .= 'onResize: function() {';
        $html .= 'wall.fitWidth();';
        $html .= '}';
        $html .= '});';
        $html .= 'wall.fitWidth();';
        $html .= '});';
        $html .= '});';
        $html .= '</script>';

        return $html;
    }
}
