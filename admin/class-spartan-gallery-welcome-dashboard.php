<?php
/**
 * "Getting Started" page (Spartan Gallery > Getting Started).
 *
 * Shown once after activation, and always reachable from the menu.
 *
 * @package Spartan_Gallery
 * @subpackage Spartan_Gallery/admin
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Redirect to the page once, right after activation (single-site only).
 */
function spartan_gallery_welcome_redirect() {
	if ( ! get_transient( 'spartan_gallery_welcome_redirect' ) ) {
		return;
	}
	delete_transient( 'spartan_gallery_welcome_redirect' );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check of WP's own bulk-activation flag.
	if ( is_network_admin() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'spartan-gallery-getting-started' ), admin_url( 'edit.php?post_type=spartan_gallery_type' ) ) );
	exit;
}
add_action( 'admin_init', 'spartan_gallery_welcome_redirect' );

function spartan_gallery_welcome_menu() {
	global $spartan_gallery_welcome_hook;

	$spartan_gallery_welcome_hook = add_submenu_page(
		'edit.php?post_type=spartan_gallery_type',
		__( 'Getting Started', 'spartan-gallery' ),
		__( 'Getting Started', 'spartan-gallery' ),
		'edit_posts',
		'spartan-gallery-getting-started',
		'spartan_gallery_welcome_page'
	);
}
add_action( 'admin_menu', 'spartan_gallery_welcome_menu' );

function spartan_gallery_welcome_styles( $hook ) {
	global $spartan_gallery_welcome_hook;

	if ( $hook !== $spartan_gallery_welcome_hook ) {
		return;
	}

	wp_enqueue_style(
		'spartan-gallery-welcome',
		plugin_dir_url( __FILE__ ) . 'css/spartan-gallery-welcome.css',
		array(),
		SPARTAN_GALLERY_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'spartan_gallery_welcome_styles' );

/**
 * Page markup.
 */
function spartan_gallery_welcome_page() {
	$new_url  = admin_url( 'post-new.php?post_type=spartan_gallery_type' );
	$list_url = admin_url( 'edit.php?post_type=spartan_gallery_type' );
	$tags_url = admin_url( 'edit-tags.php?taxonomy=sg_image_tag&post_type=attachment' );
	$img_url  = plugin_dir_url( __FILE__ ) . 'img/';

	$galleries = get_posts(
		array(
			'post_type'      => 'spartan_gallery_type',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 6,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	$features = array(
		array( 'dashicons-visibility', __( 'Lightbox', 'spartan-gallery' ), __( 'Full-screen viewer with swipe, keyboard and zoom, plus optional thumbnails, download and slideshow buttons.', 'spartan-gallery' ) ),
		array( 'dashicons-filter', __( 'Filter buttons', 'spartan-gallery' ), __( 'Tag images (Weddings, Portraits…) and let visitors filter the gallery with one click.', 'spartan-gallery' ) ),
		array( 'dashicons-update', __( 'Load more', 'spartan-gallery' ), __( 'Big galleries show the first images instantly and load the rest with a button or while scrolling.', 'spartan-gallery' ) ),
		array( 'dashicons-images-alt2', __( 'Albums', 'spartan-gallery' ), __( 'Show several galleries as cover cards; each one opens in the lightbox.', 'spartan-gallery' ) ),
		array( 'dashicons-smartphone', __( 'Responsive & fast', 'spartan-gallery' ), __( 'Columns adapt to phones and tablets, images use responsive sizes, and scripts only load where a gallery is shown.', 'spartan-gallery' ) ),
		array( 'dashicons-welcome-view-site', __( 'Live preview', 'spartan-gallery' ), __( 'See every change instantly in the editor, at desktop, tablet and phone widths — before you save.', 'spartan-gallery' ) ),
		array( 'dashicons-video-alt3', __( 'Videos', 'spartan-gallery' ), __( 'Add a YouTube, Vimeo or MP4 link to any image and it plays in the lightbox.', 'spartan-gallery' ) ),
		array( 'dashicons-share', __( 'Share & link', 'spartan-gallery' ), __( 'Every image gets its own link, plus optional Facebook, X, Pinterest and copy-link buttons.', 'spartan-gallery' ) ),
		array( 'dashicons-lock', __( 'Protection & watermark', 'spartan-gallery' ), __( 'Discourage right-click saving and show your name over the images.', 'spartan-gallery' ) ),
	);
	?>
	<div class="wrap sgw">
		<h1 class="screen-reader-text"><?php esc_html_e( 'Getting Started with Spartan Gallery', 'spartan-gallery' ); ?></h1>

		<section class="sgw-hero">
			<div class="sgw-hero-text">
				<img class="sgw-logo" src="<?php echo esc_url( $img_url . 'spartan-logo.png' ); ?>" alt="<?php esc_attr_e( 'Spartan Gallery', 'spartan-gallery' ); ?>" width="1527" height="354" />
				<p class="sgw-version">
					<?php
					/* translators: %s: plugin version */
					echo esc_html( sprintf( __( 'Version %s', 'spartan-gallery' ), SPARTAN_GALLERY_VERSION ) );
					?>
				</p>
				<p class="sgw-lead">
					<?php
					/* translators: %s: number of layouts */
					echo esc_html( sprintf( __( 'Beautiful, fast photo galleries in minutes. Pick one of %s layouts, add your images and paste a shortcode — no coding needed.', 'spartan-gallery' ), number_format_i18n( count( Spartan_Gallery_Options::layouts() ) ) ) );
					?>
				</p>
				<p class="sgw-actions">
					<a class="button button-primary button-hero" href="<?php echo esc_url( $new_url ); ?>"><?php esc_html_e( 'Create a gallery', 'spartan-gallery' ); ?></a>
					<a class="button button-hero" href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'View all galleries', 'spartan-gallery' ); ?></a>
				</p>
			</div>
			<div class="sgw-hero-art" aria-hidden="true">
				<?php foreach ( array( 'masonry', 'slider', 'mosaic', 'justified' ) as $layout ) : ?>
					<img src="<?php echo esc_url( $img_url . 'layout-' . $layout . '.svg' ); ?>" alt="" />
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sgw-section">
			<h2><?php esc_html_e( 'Three steps to your first gallery', 'spartan-gallery' ); ?></h2>
			<ol class="sgw-steps">
				<li>
					<span class="sgw-step-num">1</span>
					<h3><?php esc_html_e( 'Add images', 'spartan-gallery' ); ?></h3>
					<p>
						<?php
						printf(
							/* translators: %s: "Create a gallery" link */
							esc_html__( '%s, give it a name and open the Images tab. Drag thumbnails to change their order.', 'spartan-gallery' ),
							'<a href="' . esc_url( $new_url ) . '">' . esc_html__( 'Create a gallery', 'spartan-gallery' ) . '</a>'
						);
						?>
					</p>
				</li>
				<li>
					<span class="sgw-step-num">2</span>
					<h3><?php esc_html_e( 'Choose a look', 'spartan-gallery' ); ?></h3>
					<p><?php esc_html_e( 'Pick a layout or a one-click preset (Portfolio, Instagram, Products, Photography, Slideshow). The live preview updates as you go.', 'spartan-gallery' ); ?></p>
				</li>
				<li>
					<span class="sgw-step-num">3</span>
					<h3><?php esc_html_e( 'Publish it', 'spartan-gallery' ); ?></h3>
					<p><?php esc_html_e( 'Click "Copy" next to the shortcode and paste it into any page, or add the "Spartan Gallery Block" in the block editor.', 'spartan-gallery' ); ?></p>
				</li>
			</ol>
		</section>

		<?php if ( $galleries ) : ?>
			<section class="sgw-section">
				<h2><?php esc_html_e( 'Your galleries', 'spartan-gallery' ); ?></h2>
				<table class="widefat striped sgw-galleries">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Gallery', 'spartan-gallery' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Layout', 'spartan-gallery' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Images', 'spartan-gallery' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Shortcode', 'spartan-gallery' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$layout_names = Spartan_Gallery_Options::layouts();
						foreach ( $galleries as $gallery ) :
							$settings = Spartan_Gallery_Settings::get( $gallery->ID );
							$layout   = $settings['image_layout_select'];
							$count    = count( Spartan_Gallery_Renderer::gallery_attachments( $settings, get_post_meta( $gallery->ID, 'spartan_gallery_uploaded_images', true ) ) );
							$title    = get_the_title( $gallery );
							?>
							<tr>
								<td>
									<a class="row-title" href="<?php echo esc_url( get_edit_post_link( $gallery->ID ) ); ?>"><?php echo esc_html( '' !== $title ? $title : __( '(no title)', 'spartan-gallery' ) ); ?></a>
									<?php if ( 'publish' !== $gallery->post_status ) : ?>
										<span class="sgw-status"><?php echo esc_html( get_post_status_object( $gallery->post_status )->label ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( isset( $layout_names[ $layout ] ) ? $layout_names[ $layout ][0] : $layout ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
								<td><code>[spartan_gallery id="<?php echo absint( $gallery->ID ); ?>"]</code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		<?php endif; ?>

		<section class="sgw-section">
			<h2>
				<?php
				/* translators: %s: number of layouts */
				echo esc_html( sprintf( __( '%s layouts', 'spartan-gallery' ), number_format_i18n( count( Spartan_Gallery_Options::layouts() ) ) ) );
				?>
			</h2>
			<div class="sgw-layouts">
				<?php foreach ( Spartan_Gallery_Options::layouts() as $value => $data ) : ?>
					<figure class="sgw-layout">
						<img src="<?php echo esc_url( $img_url . 'layout-' . $value . '.svg' ); ?>" alt="" />
						<figcaption>
							<strong><?php echo esc_html( $data[0] ); ?></strong>
							<span><?php echo esc_html( $data[1] ); ?></span>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sgw-section">
			<h2><?php esc_html_e( 'Built in', 'spartan-gallery' ); ?></h2>
			<div class="sgw-features">
				<?php foreach ( $features as $feature ) : ?>
					<div class="sgw-feature">
						<span class="dashicons <?php echo esc_attr( $feature[0] ); ?>" aria-hidden="true"></span>
						<h3><?php echo esc_html( $feature[1] ); ?></h3>
						<p><?php echo esc_html( $feature[2] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sgw-section sgw-columns">
			<div class="sgw-card">
				<h2><?php esc_html_e( 'Shortcodes', 'spartan-gallery' ); ?></h2>
				<dl class="sgw-shortcodes">
					<dt><code>[spartan_gallery id="123"]</code></dt>
					<dd><?php esc_html_e( 'One gallery. The ID is shown on the gallery list and in the gallery editor.', 'spartan-gallery' ); ?></dd>
					<dt><code>[spartan_album]</code></dt>
					<dd><?php esc_html_e( 'All published galleries as cover cards.', 'spartan-gallery' ); ?></dd>
					<dt><code>[spartan_album ids="12,15" columns="3" titles="no"]</code></dt>
					<dd><?php esc_html_e( 'Only the chosen galleries, in that order.', 'spartan-gallery' ); ?></dd>
				</dl>
			</div>
			<div class="sgw-card">
				<h2><?php esc_html_e( 'Tips', 'spartan-gallery' ); ?></h2>
				<ul class="sgw-tips">
					<li>
						<?php
						printf(
							/* translators: %s: "Gallery Tags" link */
							esc_html__( 'Organize images with %s, then turn on "Filter buttons" in the gallery\'s Filter & Loading tab.', 'spartan-gallery' ),
							'<a href="' . esc_url( $tags_url ) . '">' . esc_html__( 'Gallery Tags', 'spartan-gallery' ) . '</a>'
						);
						?>
					</li>
					<li><?php esc_html_e( 'Write a Caption for an image in the Media Library to show it in the lightbox.', 'spartan-gallery' ); ?></li>
					<li><?php esc_html_e( 'For Before / After, add images in pairs: the 1st image is "before", the 2nd "after", and so on.', 'spartan-gallery' ); ?></li>
					<li><?php esc_html_e( 'Galleries with many images load faster with "Load more" turned on.', 'spartan-gallery' ); ?></li>
				</ul>
			</div>
		</section>

		<p class="sgw-footer">
			<?php
			printf(
				/* translators: %s: website link */
				esc_html__( 'Questions or ideas? Visit %s.', 'spartan-gallery' ),
				'<a href="https://freelancermartin.ee/" target="_blank" rel="noopener noreferrer">freelancermartin.ee</a>'
			);
			?>
		</p>
	</div>
	<?php
}
