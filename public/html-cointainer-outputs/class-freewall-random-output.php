<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

Class Freewall_Random_Container
{
    static function display_random_container( $attachments, $gallery_id )
    {
        $CSSClass = new Spartan_Gallery_Generated_CSS_Output();

        $image_options_add_extra_css         = get_post_meta( $gallery_id, 'add_extra_css', true );
        $image_options_display_original      = get_post_meta( $gallery_id, 'display_original', true );
        $image_options_img_margin            = absint( get_post_meta( $gallery_id, 'img_margin', true ) );
        $image_options_thumbnail_options     = get_post_meta( $gallery_id, 'thumbnail_options', true );
        $image_options_textbox_height_range  = absint( get_post_meta( $gallery_id, 'textbox_height_range', true ) );
        $image_options_display_text          = get_post_meta( $gallery_id, 'display_text', true );
        $image_options_info_background_color = get_post_meta( $gallery_id, 'info_background_color', true );
        $image_options_container_font_size   = absint( get_post_meta( $gallery_id, 'container_font_size', true ) );
        $image_options_thumbnail_size        = get_post_meta( $gallery_id, 'thumbnail_size', true );
        $image_options_font_family           = get_post_meta( $gallery_id, 'font_family', true );
        $safe_extra_css                      = wp_strip_all_tags( $image_options_add_extra_css );

        $html = '';
        $html .= '<style>' . $safe_extra_css . '</style>';
        $html .= '<div style="' . $CSSClass->output_php_Css( $gallery_id ) . '" class="spartan-gallery-wrapper">';
        $html .= '<div id="freewall-random" class="masonary">';

        $img_size = array( '120', '160', '180', '240', '280', '320' );

        foreach ( (array) $attachments as $attachment_id => $attachment_val ) {

            $image_attributes = wp_get_attachment_image_src( $attachment_val->ID, $image_options_thumbnail_size );

            if ( $image_options_display_original == true xor $image_options_thumbnail_options == true ) {
                $html .= '<div class="freewall-brick brick" data-width="' . $img_size[ array_rand( $img_size, 1 ) ] . '" data-height="' . $img_size[ array_rand( $img_size, 1 ) ] . '" data-delay="' . absint( $attachment_id ) . '">';
            }

            $html .= '<a href="' . esc_url( $attachment_val->guid ) . '" class="js-img-viwer img-true-location" data-caption="" data-id="' . absint( $attachment_id ) . '">';

            if ( $image_options_display_original == true and $image_options_thumbnail_options == false ) {
                $html .= '<img class="image" style="height:240px; width:100%;" src="' . esc_url( $attachment_val->guid ) . '" />';
            } elseif ( $image_options_thumbnail_options == true and $image_options_display_original == false ) {
                $html .= '<img style="height:240px; width:100%;" src="' . esc_url( $image_attributes[0] ) . '" />';
            } else {
                return '<p>' . esc_html__( 'Image Options: "Display Original" or "Display Thumbnail" must be enabled, but not both simultaneously.', 'spartan-gallery' ) . '</p>';
            }

            if ( ! empty( $attachment_id ) && true == $image_options_display_text ) {
                $html .= '<style>
                    .image { max-height: calc( 100% - ' . $image_options_textbox_height_range . '% ) !important; }
                    .info {
                        max-height: calc( 100% - ' . $image_options_textbox_height_range . '% ) !important;
                        background-color: ' . esc_html( $image_options_info_background_color ) . ';
                        position: relative;
                        margin: 0px;
                        font-size: ' . $image_options_container_font_size . 'px !important;
                    }
                    .info>p, .info>h5 {
                        font-size: '   . esc_html( $image_options_font_family['font-size'] )   . ';
                        color: '       . esc_html( $image_options_font_family['color'] )        . ';
                        font-style: '  . esc_html( $image_options_font_family['font-style'] )   . ';
                        font-family: ' . esc_html( $image_options_font_family['font-family'] )  . ';
                        line-height: ' . esc_html( $image_options_font_family['line-height'] )  . ';
                        font-weight: ' . esc_html( $image_options_font_family['font-weight'] )  . ';
                        text-align: '  . esc_html( $image_options_font_family['text-align'] )   . ';
                    }
                    .info>button {
                        font-size: ' . esc_html( $image_options_font_family['font-size'] ) . ';
                        color: '     . esc_html( $image_options_font_family['color'] )     . ';
                    }
                    ' . $safe_extra_css . '
                </style>';

                $html .= '<div class="info">';
                if ( ! empty( $attachment_val->post_title ) ) {
                    $html .= '<h5 class="text spartan-gallery-img-title">' . esc_html( $attachment_val->post_title ) . '</h5>';
                }
                if ( ! empty( $attachment_val->post_content ) ) {
                    $html .= '<p class="text spartan-gallery-img-description">' . wp_kses_post( $attachment_val->post_content ) . '</p>';
                }
                if ( ! empty( $attachment_val->guid ) ) {
                    $html .= '<td colspan="3" style="background-color:#005673; text-align:right; padding: 4px 0px;">
                        <button class="spartan-gallery-img-button text" onclick="parent.open(\'' . esc_js( esc_url( $attachment_val->guid ) ) . '\')">' . esc_html( $attachment_val->post_title ) . '</button></td>';
                }
                $html .= '</div>';
            }

            $html .= '</a>';

            if ( $image_options_display_original == true xor $image_options_thumbnail_options == true ) {
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        $html .= '
            <script>
                jQuery( document ).ready( function( $ ){
                    var Imagewall = new Freewall("#freewall-random");
                    Imagewall.reset({
                        selector: ".freewall-brick",
                        animate: true,
                        cellW: 200,
                        cellH: "auto",
                        gutterY: ' . $image_options_img_margin . ',
                        gutterX: ' . $image_options_img_margin . ',
                        onResize: function() {
                            Imagewall.fitWidth();
                        }
                    });
                    $( document ).ready(function() {
                        Imagewall.fitWidth();
                    });
                });
            </script>';

        return $html;
    }
}
