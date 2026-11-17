=== Spartan Gallery ===

Contributors:       Freelancer Martin
Tags:               gallery, portfolio, photo gallery, gutenberg, responsive
Requires at least: 4.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag:         1.7.0
License:            GPLv2 or later
License URI:        http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Spartan Gallery is a responsive, multipurpose portfolio gallery plugin for WordPress. It works well for agencies, personal portfolios, architecture and photography studios, product showcases, artist portfolios, and blogs.

Ten layouts are included:

* **Grid** – even columns (1–6), images cropped to the shape you choose.
* **Masonry** – uncropped images packed into columns, Pinterest-style.
* **Justified** – rows of equal height that always fill the full width.
* **Mosaic** – a grid mixing large and small tiles.
* **Polaroid** – tilted instant-photo cards that straighten on hover.
* **Hexagon** – a honeycomb grid.
* **3D Slider** – a touch-friendly 3D coverflow carousel.
* **Carousel** – several images side by side, swipeable, optional autoplay.
* **Slideshow** – one large image with a thumbnail strip, optional autoplay.
* **Before / After** – drag a handle to compare pairs of images.

Every layout (except Before / After) can open images in a full-screen lightbox with swipe, keyboard navigation, captions from the Media Library, a thumbnail strip, and optional download and slideshow buttons. Choose a hover effect (zoom, fade, darken, or title overlay), rounded corners and image quality per gallery.

**Filterable galleries:** give images "Gallery Tags" in the Media Library and turn on filter buttons, so visitors can show only Weddings, Portraits, etc.

**Load more:** big galleries can show the first images and load the rest with a "Load more" button or automatically while scrolling.

**Albums:** show several galleries as cover cards with `[spartan_album]` or the "Spartan Album" block; clicking a cover opens that gallery in the lightbox.

Galleries are responsive (columns reduce automatically on tablets and phones), use WordPress' responsive image sizes, and only load their scripts and styles on pages that actually contain a gallery. The front end does not require jQuery.

Each gallery has its own options panel with one-click presets (Portfolio, Instagram, Products, Photography, Slideshow), a live preview with desktop/tablet/phone widths, drag-and-drop image ordering, and a copy-the-shortcode button. A Gutenberg block adds galleries anywhere in the block editor.

= Visit Example Site  =

* [Spartan Gallery Official Site  ](https://freelancermartin.ee/)


= See a how Spartan Gallery works =

https://www.youtube.com/watch?v=VF1u_1SLHjs



 = Shortcode =

`[spartan_gallery id="123"]`

Replace 123 with the gallery ID shown in the Spartan Gallery list. (`page_id` is still accepted as an alias.)

= Albums =

`[spartan_album]` lists every published gallery as a cover card.
`[spartan_album ids="12,15,18" columns="3" titles="no"]` shows only the chosen galleries, in that order.

= Third-party libraries =

Bundled in `public/vendor/`, both MIT licensed:

* [Swiper](https://swiperjs.com/) 14.4.0 – 3D slider, carousel and slideshow. Source: https://github.com/nolimits4web/swiper
* [GLightbox](https://biati-digital.github.io/glightbox/) 3.3.1 – lightbox. Source: https://github.com/biati-digital/glightbox

The Masonry layout uses the Masonry and imagesLoaded scripts that ship with WordPress core.


== Installation ==

If installing the plugin from wordpress.org:

1. Upload or install the entire /spartan-gallery directory to the /wp-content/plugins/ directory, or install directly through the 'Plugins' screen in WordPress.
2. Activate Spartan Gallery through the 'Plugins' menu in WordPress.
3. Go to Spartan Gallery > Add New to create a gallery.
4. Upload images, choose a layout, and select image settings.
5. Copy the shortcode (or use the Gutenberg block) wherever you'd like the gallery to appear.



== Frequently Asked Questions ==

coming soon

== Screenshots ==

1. Banner for plugin
2. Banner for plugin smaller
3. Spartan Gallery logo
4. Layout Example 1
5. Layout Example 2
6. Layout Example 3
7. Layout Example 4
8. Layout Example 5
9. Layout Example 6
10. Layout Example 7
11. Layout Example 8

== Changelog ==

= 1.7.0 =
* Filterable galleries: new "Gallery Tags" for images (Media > Gallery Tags) and optional filter buttons above Grid, Masonry, Justified, Mosaic and Polaroid galleries.
* "Load more" button or infinite scroll for large galleries, with a configurable batch size. Works together with the filters.
* Albums: [spartan_album] shortcode and "Spartan Album" block show galleries as cover cards that open in the lightbox.
* The lightbox only includes images that match the active filter.

= 1.6.0 =
* New layouts: Mosaic, Polaroid, Carousel, Slideshow (with thumbnails) and Before / After.
* Live preview in the gallery editor, with desktop, tablet and phone widths.
* One-click presets: Portfolio, Instagram, Products, Photography, Slideshow.
* Drag and drop to reorder images.
* Copy-shortcode button on the gallery edit screen.
* Hover effects: zoom, fade, darken, or darken with the image title.
* Lightbox: optional thumbnail strip, download button and slideshow button.
* Autoplay for the 3D Slider, Carousel and Slideshow.
* Simpler settings: one "Image quality" choice replaces the old original/thumbnail switches and size list; container settings no longer need an on/off switch and default to full width; one border field instead of four.
* Existing galleries keep their look; old settings are converted automatically.

= 1.5.0 =
* New layouts: Grid, Masonry and Justified replace the four Freewall layouts. Existing galleries are switched to the closest new layout automatically (Chaotic/Pinterest → Masonry, Random → Justified, Fixed → Grid).
* New settings: number of columns, grid aspect ratio, justified row height, gap between images.
* 3D Slider rebuilt on Swiper (coverflow effect, touch, keyboard and pagination). Several sliders can now be used on one page.
* New lightbox (GLightbox) with titles/captions from the Media Library; can be turned off per gallery.
* Removed all jQuery front-end libraries (Freewall, Flipster, SmartPhoto, responsiveGallery, Modernizr).
* Scripts and styles are now only loaded on pages that contain a gallery.
* Images use WordPress responsive sizes (srcset) and alt text.
* Draft and private galleries are no longer shown to visitors.

= 1.4.0 =
* Removed the Redux Framework dependency; the gallery options panel is now a native WordPress metabox (no third-party plugin required).
* Fixed images being stretched/distorted in all six layouts.
* Fixed several settings (thumbnail size, adjustable image, container settings) not saving correctly when turned off.
* Fixed slider controls not being usable in the options panel.
* Removed the "display text under image" caption feature.
* Various security hardening and bug fixes.

= 1.3.0 =
* Added Gutenberg block support.
* UI improvements and layout fixes.

= 1.2.0 =
* Updated user interface; new core class system for the plugin.

= 1.0.0 =
* First stable release.

== Upgrade Notice ==

= 1.7.0 =
Adds filter buttons (via Gallery Tags), load more / infinite scroll and albums. No changes to existing galleries.

= 1.6.0 =
Adds five new layouts, a live preview and presets. Existing gallery settings are converted automatically.

= 1.5.0 =
The Freewall layouts are replaced by Grid, Masonry and Justified. Existing galleries switch to the closest new layout automatically; check them and adjust columns or row height if needed.

= 1.4.0 =
Removes the Redux Framework dependency in favor of a built-in options panel — your existing gallery settings carry over automatically. If Redux Framework was installed only for this plugin, it can now be deactivated.
