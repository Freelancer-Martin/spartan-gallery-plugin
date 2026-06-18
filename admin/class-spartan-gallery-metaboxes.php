<?php
/**
* Function to include shortcode and other metaboxes to plugin
*
* @since Spartan Gallery 1.0.2
* @return void
*/
if ( ! defined( 'WPINC' ) ) {
    die;
}

function spartan_gallery_meta_box_add()
{
    add_meta_box( 'new-spartan-gallery-box', 'Shortcode', 'spartan_gallery_paybal_meta_box_cb', 'spartan_gallery_type', 'normal', 'low' );
}
add_action( 'add_meta_boxes', 'spartan_gallery_meta_box_add' );

function spartan_gallery_paybal_meta_box_cb()
{
    $gallery_id = absint( get_the_ID() );
?>

  <div>
      <div class="postbox">
          <h3 style="border-bottom: 1px solid #EEE;background: #f7f7f7;"><span class="tcode"><?php esc_html_e( 'Shortcode', 'spartan-gallery' ); ?></span></h3>
          <div class="inside" style="padding-bottom:20px;display: block;">
              <table width="100%">
              <tr><td>
                  <p><code style="color:#A71D5D;">[spartan_gallery id='<?php echo esc_html( $gallery_id ); ?>']</code></p>
              </td></tr>
              </table>
          </div>
      </div>

      <div class="postbox">
          <h3 style="border-bottom: 1px solid #EEE;background: #f7f7f7;"><span class="tcode"><?php esc_html_e( 'PHP code', 'spartan-gallery' ); ?></span></h3>
          <div class="inside" style="padding-bottom:20px;display: block;">
              <table width="100%">
              <tr><td>
                  <p><code style="color:#A71D5D;">echo do_shortcode("[spartan_gallery id='<?php echo esc_html( $gallery_id ); ?>']");</code></p>
              </td></tr>
              </table>
          </div>
      </div>

      <div class="postbox">
          <h3 style="border-bottom: 1px solid #EEE;background: #f7f7f7;"><span class="tcode"><?php esc_html_e( 'Do you like this plugin?', 'spartan-gallery' ); ?></span></h3>
          <div class="inside" style="display: block;">
              <img src="<?php echo esc_url( plugin_dir_url( '' ) . 'spartan-gallery/admin/img/index.jpeg' ); ?>" title="<?php esc_attr_e( 'buy me a coffee', 'spartan-gallery' ); ?>" style="margin: 5px; float:left;" />
              <p><?php esc_html_e( "Hi! I'm Freelancer Martin, developer of this plugin.", 'spartan-gallery' ); ?></p>
              <p><?php esc_html_e( "I've been spending many hours to develop this plugin.", 'spartan-gallery' ); ?><br />
              <?php esc_html_e( 'If you like and use this plugin and would like to see future development, you can buy me a cup of coffee.', 'spartan-gallery' ); ?></p>
              <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CVJ7GE7QM5F3N" target="_blank" rel="nofollow"><img src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" alt="" /></a>
              <div style="clear:both;"></div>
          </div>
      </div>
  </div>

<?php
}
