<?php
/**
 * Admin helpers around galleries and their images:
 *  - "Gallery video URL" field on images (Media Library / media modal).
 *  - "Duplicate" row action on the gallery list.
 *  - Editing title / caption / alt / tags / video for all gallery images
 *    at once from the gallery editor (rows + save).
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/admin
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery_Admin_Tools {

	const POST_TYPE        = 'spartan_gallery_type';
	const DUPLICATE_ACTION = 'spartan_gallery_duplicate';
	const ROWS_ACTION      = 'spartan_gallery_image_rows';

	public static function init() {
		add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'video_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( __CLASS__, 'save_video_field' ), 10, 2 );

		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_post_' . self::DUPLICATE_ACTION, array( __CLASS__, 'duplicate' ) );

		add_action( 'wp_ajax_' . self::ROWS_ACTION, array( __CLASS__, 'ajax_rows' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Video URL on images                                                 */
	/* ------------------------------------------------------------------ */

	public static function video_field( $fields, $post ) {
		if ( ! wp_attachment_is_image( $post ) ) {
			return $fields;
		}
		$fields['sg_video_url'] = array(
			'label' => __( 'Gallery video URL', 'spartan-gallery' ),
			'input' => 'text',
			'value' => (string) get_post_meta( $post->ID, '_sg_video_url', true ),
			'helps' => __( 'Optional. YouTube, Vimeo or .mp4 link: Spartan Gallery shows this image as the poster and plays the video in the lightbox.', 'spartan-gallery' ),
		);
		return $fields;
	}

	public static function save_video_field( $post, $attachment ) {
		if ( isset( $attachment['sg_video_url'] ) ) {
			self::update_video( $post['ID'], $attachment['sg_video_url'] );
		}
		return $post;
	}

	private static function update_video( $attachment_id, $raw ) {
		$url = esc_url_raw( trim( (string) $raw ), array( 'http', 'https' ) );
		if ( $url && Spartan_Gallery_Renderer::is_video_url( $url ) ) {
			update_post_meta( $attachment_id, '_sg_video_url', $url );
		} else {
			delete_post_meta( $attachment_id, '_sg_video_url' );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Duplicate                                                           */
	/* ------------------------------------------------------------------ */

	public static function row_actions( $actions, $post ) {
		if ( self::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) || ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => self::DUPLICATE_ACTION,
					'post'   => $post->ID,
				),
				admin_url( 'admin-post.php' )
			),
			self::DUPLICATE_ACTION . '_' . $post->ID
		);
		/* translators: %s: gallery title */
		$label = sprintf( __( 'Duplicate &#8220;%s&#8221;', 'spartan-gallery' ), get_the_title( $post ) );
		$actions['sg_duplicate'] = '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( wp_strip_all_tags( $label ) ) . '">' . esc_html__( 'Duplicate', 'spartan-gallery' ) . '</a>';
		return $actions;
	}

	public static function duplicate() {
		$source_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( self::DUPLICATE_ACTION . '_' . $source_id );

		$source = $source_id ? get_post( $source_id ) : null;
		if ( ! $source || self::POST_TYPE !== $source->post_type || ! current_user_can( 'edit_post', $source_id ) || ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to duplicate this gallery.', 'spartan-gallery' ), 403 );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'draft',
				/* translators: %s: original gallery title */
				'post_title'  => sprintf( __( '%s (copy)', 'spartan-gallery' ), $source->post_title ),
				'post_author' => get_current_user_id(),
			),
			true
		);
		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}

		$skip = array( '_edit_lock', '_edit_last', '_wp_old_slug' );
		foreach ( get_post_meta( $source_id ) as $key => $values ) {
			if ( in_array( $key, $skip, true ) ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
			}
		}

		wp_safe_redirect( get_edit_post_link( $new_id, 'raw' ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/* Image details (bulk edit)                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Editable rows for the given images. Each row also carries the values
	 * it was rendered with, so saving only touches what was changed here
	 * (and never wipes tags/video that were added elsewhere meanwhile).
	 *
	 * @param int[] $ids
	 */
	public static function rows_html( array $ids ) {
		$html = '';
		foreach ( Spartan_Gallery_Renderer::get_attachments( $ids ) as $att ) {
			$id    = $att->ID;
			$tags  = wp_get_object_terms( $id, Spartan_Gallery_Taxonomy::TAXONOMY, array( 'fields' => 'names' ) );
			$tags  = is_wp_error( $tags ) ? '' : implode( ', ', $tags );
			$vals  = array(
				'title'   => $att->post_title,
				'alt'     => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
				'caption' => $att->post_excerpt,
				'tags'    => $tags,
				'video'   => (string) get_post_meta( $id, '_sg_video_url', true ),
			);
			$thumb = wp_get_attachment_image_src( $id, 'thumbnail' );
			$name  = 'sg_img[' . $id . ']';

			$html .= '<tr class="sg-detail-row" data-id="' . esc_attr( $id ) . '">';
			$html .= '<td class="sg-detail-thumb"><img src="' . esc_url( $thumb ? $thumb[0] : '' ) . '" alt="" /></td>';
			$html .= '<td><label>' . esc_html__( 'Title', 'spartan-gallery' ) . '<input type="text" name="' . esc_attr( $name . '[title]' ) . '" value="' . esc_attr( $vals['title'] ) . '" /></label>';
			$html .= '<label>' . esc_html__( 'Alt text', 'spartan-gallery' ) . '<input type="text" name="' . esc_attr( $name . '[alt]' ) . '" value="' . esc_attr( $vals['alt'] ) . '" /></label></td>';
			$html .= '<td><label>' . esc_html__( 'Caption', 'spartan-gallery' ) . '<textarea rows="3" name="' . esc_attr( $name . '[caption]' ) . '">' . esc_textarea( $vals['caption'] ) . '</textarea></label></td>';
			$html .= '<td><label>' . esc_html__( 'Gallery Tags', 'spartan-gallery' ) . '<input type="text" name="' . esc_attr( $name . '[tags]' ) . '" value="' . esc_attr( $vals['tags'] ) . '" placeholder="' . esc_attr__( 'e.g. Weddings, Portraits', 'spartan-gallery' ) . '" /></label>';
			$html .= '<label>' . esc_html__( 'Video URL', 'spartan-gallery' ) . '<input type="url" name="' . esc_attr( $name . '[video]' ) . '" value="' . esc_attr( $vals['video'] ) . '" placeholder="https://youtu.be/…" /></label>';
			foreach ( $vals as $key => $value ) {
				$html .= '<input type="hidden" name="' . esc_attr( 'sg_img_orig[' . $id . '][' . $key . ']' ) . '" value="' . esc_attr( $value ) . '" />';
			}
			$html .= '</td></tr>';
		}
		return $html;
	}

	public static function ajax_rows() {
		check_ajax_referer( Spartan_Gallery_Options::PREVIEW_ACTION, 'sg_preview_nonce' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( null, 403 );
		}
		$ids = Spartan_Gallery_Settings::parse_image_ids( isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '' );
		$ids = array_values( array_filter( $ids, function ( $id ) {
			return current_user_can( 'edit_post', $id );
		} ) );
		wp_send_json_success( array( 'html' => self::rows_html( $ids ) ) );
	}

	/**
	 * Save edited image details posted with the gallery form.
	 *
	 * @param array $rows  sg_img[ID][field] (unslashed).
	 * @param array $origs sg_img_orig[ID][field] (unslashed).
	 */
	public static function save_rows( $rows, $origs ) {
		if ( ! is_array( $rows ) ) {
			return;
		}
		foreach ( $rows as $id => $fields ) {
			$id = absint( $id );
			if ( ! $id || ! is_array( $fields ) || 'attachment' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}
			$orig    = isset( $origs[ $id ] ) && is_array( $origs[ $id ] ) ? $origs[ $id ] : array();
			$changed = function ( $key ) use ( $fields, $orig ) {
				return isset( $fields[ $key ] ) && ( ! isset( $orig[ $key ] ) || (string) $fields[ $key ] !== (string) $orig[ $key ] );
			};

			$post = array();
			if ( $changed( 'title' ) ) {
				$post['post_title'] = sanitize_text_field( $fields['title'] );
			}
			if ( $changed( 'caption' ) ) {
				$post['post_excerpt'] = wp_kses_post( $fields['caption'] );
			}
			if ( $post ) {
				$post['ID'] = $id;
				wp_update_post( wp_slash( $post ) );
			}
			if ( $changed( 'alt' ) ) {
				update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $fields['alt'] ) );
			}
			$tax = get_taxonomy( Spartan_Gallery_Taxonomy::TAXONOMY );
			if ( $changed( 'tags' ) && $tax && current_user_can( $tax->cap->assign_terms ) ) {
				$names = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( $fields['tags'] ) ) ) );
				wp_set_object_terms( $id, array_values( $names ), Spartan_Gallery_Taxonomy::TAXONOMY );
			}
			if ( $changed( 'video' ) ) {
				self::update_video( $id, $fields['video'] );
			}
		}
	}
}

Spartan_Gallery_Admin_Tools::init();
