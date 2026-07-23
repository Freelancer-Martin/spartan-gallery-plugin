=== Spartan Gallery ===

Contributors:       Freelancer Martin
Tags:               gallery, portfolio, photo gallery, gutenberg, responsive
Requires at least: 4.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag:         1.4.0
License:            GPLv2 or later
License URI:        http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Spartan Gallery is a responsive, multipurpose portfolio gallery plugin for WordPress. It works well for agencies, personal portfolios, architecture and photography studios, product showcases, artist portfolios, and blogs.

Six layout styles are included: Freewall Chaotic, Random, Fixed, Pinterest, Hexagon, and 3D Slider. Each gallery has its own options panel to control image size, thumbnail behavior, container styling, and custom CSS, plus a Gutenberg block for adding galleries anywhere in the block editor.

= Visit Example Site  =

* [Spartan Gallery Official Site  ](https://freelancermartin.ee/)


= See a how Spartan Gallery works =

https://www.youtube.com/watch?v=VF1u_1SLHjs



 = Shortcode Parameters =

`
* page_id
`


= Basic example =

`[spartan_gallery page_id=""]`


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

= 1.4.0 =
Removes the Redux Framework dependency in favor of a built-in options panel — your existing gallery settings carry over automatically. If Redux Framework was installed only for this plugin, it can now be deactivated.
