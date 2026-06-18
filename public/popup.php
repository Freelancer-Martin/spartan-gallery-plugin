<?php
/**
 * Spartan Gallery Plugin popup screen
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

function button_url_get_meta( $value ) {
	global $post;

	$field = get_post_meta( $post->ID, $value, true );
	if ( ! empty( $field ) ) {
		return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
	} else {
		return false;
	}
}

function button_url_add_meta_box() {
	add_meta_box(
		'button_url-button-url',
		__( 'Image Button Links', 'spartan-gallery' ),
		'button_url_html',
		'spartan_gallery_type',
		'normal',
		'low'
	);
}
add_action( 'add_meta_boxes', 'button_url_add_meta_box' );

function button_url_html( $post ) {
	wp_nonce_field( '_button_url_nonce', 'button_url_nonce' );

	$args = array(
		'post_type'   => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'meta_query'  => array(
			array(
				'key'     => 'spartan_gallery',
				'compare' => '=',
			)
		)
	);

	$attachments = get_posts( $args );

	foreach ( (array) $attachments as $attachment_id => $attachment_value ) {
		echo '<div class="popup" data-popup="popup-' . absint( $attachment_value->ID ) . '">';
		echo '<div class="popup-inner">';

		echo '<p>';
		echo '<a><label for="button_url_buttonurl' . absint( $attachment_value->ID ) . '">' . esc_html__( 'Insert Button Url', 'spartan-gallery' ) . '</label></a>';
		echo '<a><input maxlength="160" type="text" name="button_url_buttonurl' . absint( $attachment_value->ID ) . '" id="button_url_buttonurl' . absint( $attachment_value->ID ) . '" value="' . esc_attr( button_url_get_meta( 'button_url_buttonurl' . $attachment_value->ID ) ) . '"></a>';
		echo '</p>';

		echo '<p>';
		echo '<a><label for="button_url_buttontitle' . absint( $attachment_value->ID ) . '">' . esc_html__( 'Insert Button Title', 'spartan-gallery' ) . '</label></a>';
		echo '<a><input maxlength="160" type="text" name="button_url_buttontitle' . absint( $attachment_value->ID ) . '" id="button_url_buttontitle' . absint( $attachment_value->ID ) . '" value="' . esc_attr( button_url_get_meta( 'button_url_buttontitle' . $attachment_value->ID ) ) . '"></a>';
		echo '</p>';

		echo '<p>';
		echo '<a><label for="imgtitle' . absint( $attachment_value->ID ) . '">' . esc_html__( 'Insert Image Title', 'spartan-gallery' ) . '</label></a>';
		echo '<a><input maxlength="160" type="text" name="imgtitle' . absint( $attachment_value->ID ) . '" id="imgtitle' . absint( $attachment_value->ID ) . '" value="' . esc_attr( button_url_get_meta( 'imgtitle' . $attachment_value->ID ) ) . '"></a>';
		echo '</p>';

		echo '<p>';
		echo '<a><label for="imgdescription' . absint( $attachment_value->ID ) . '">' . esc_html__( 'Insert Image Description', 'spartan-gallery' ) . '</label></a>';
		echo '<a><input maxlength="160" type="text" name="imgdescription' . absint( $attachment_value->ID ) . '" id="imgdescription' . absint( $attachment_value->ID ) . '" value="' . esc_attr( button_url_get_meta( 'imgdescription' . $attachment_value->ID ) ) . '"></a>';
		echo '</p>';

		echo '<p><a id="close-popup" data-popup-close="popup-' . absint( $attachment_value->ID ) . '" href="#">' . esc_html__( 'Close', 'spartan-gallery' ) . '</a></p>';
		echo '<a id="close-popup" class="popup-close" data-popup-close="popup-' . absint( $attachment_value->ID ) . '" href="#">x</a>';
		echo '</div>';
		echo '</div>';
	}
}

function button_url_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['button_url_nonce'] ) || ! wp_verify_nonce( $_POST['button_url_nonce'], '_button_url_nonce' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$args = array(
		'post_type'   => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'meta_query'  => array(
			array(
				'key'     => 'spartan_gallery',
				'compare' => '=',
			)
		)
	);

	$attachments = get_posts( $args );

	foreach ( (array) $attachments as $attachments_id => $attachment_value ) {
		$id = $attachment_value->ID;

		if ( isset( $_POST[ 'button_url_buttonurl' . $id ] ) ) {
			update_post_meta( $post_id, 'button_url_buttonurl' . $id, sanitize_text_field( wp_unslash( $_POST[ 'button_url_buttonurl' . $id ] ) ) );
		}

		if ( isset( $_POST[ 'button_url_buttontitle' . $id ] ) ) {
			update_post_meta( $post_id, 'button_url_buttontitle' . $id, sanitize_text_field( wp_unslash( $_POST[ 'button_url_buttontitle' . $id ] ) ) );
		}

		if ( isset( $_POST[ 'imgtitle' . $id ] ) ) {
			update_post_meta( $post_id, 'imgtitle' . $id, sanitize_text_field( wp_unslash( $_POST[ 'imgtitle' . $id ] ) ) );
		}

		if ( isset( $_POST[ 'imgdescription' . $id ] ) ) {
			update_post_meta( $post_id, 'imgdescription' . $id, sanitize_text_field( wp_unslash( $_POST[ 'imgdescription' . $id ] ) ) );
		}
	}
}
add_action( 'save_post', 'button_url_save' );
