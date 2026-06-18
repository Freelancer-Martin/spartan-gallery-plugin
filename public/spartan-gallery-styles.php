<?php
/**
 * Spartan Gallery Plugin plugin style functions
 *
 * @category  WordPress_Spartan_Gallery_Plugin
 * @package   Spartan_Gallery
 * @author    Freelancer Martin
 * @license   GPL-2.0+
 * @link      https://themes.developerforwebsites.com
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

Class Spartan_Gallery_Generated_CSS_Output
{
    static function output_php_Css( $gallery_id )
    {
        $image_options_container_settings_on_off   = get_post_meta( $gallery_id, 'container_settings_on_off', true );
        $image_options_container_height_width_range = get_post_meta( $gallery_id, 'container_height_width_range', true );
        $image_options_container_background_color  = get_post_meta( $gallery_id, 'container_background_color', true );
        $image_options_container_border            = get_post_meta( $gallery_id, 'container_border', true );

        $css = '';

        if ( $image_options_container_settings_on_off == true ) {
            $css .= '
                border-top: '    . esc_attr( $image_options_container_border['border-top'] )    . ';
                border-right: '  . esc_attr( $image_options_container_border['border-right'] )  . ';
                border-bottom: ' . esc_attr( $image_options_container_border['border-bottom'] ) . ';
                border-left: '   . esc_attr( $image_options_container_border['border-left'] )   . ';
                border-style: '  . esc_attr( $image_options_container_border['border-style'] )  . ';
                border-color: '  . esc_attr( $image_options_container_border['border-color'] )  . ';
                background-color: ' . esc_attr( $image_options_container_background_color ) . ';
                height: '        . esc_attr( $image_options_container_height_width_range['height'] ) . ';
                width: '         . esc_attr( $image_options_container_height_width_range['width'] )  . ';
            ';
        }

        return $css;
    }
}
