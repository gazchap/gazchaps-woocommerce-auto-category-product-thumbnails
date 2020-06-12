=== GazChap's WooCommerce Auto Category Product Thumbnails ===
Contributors: gazchap
Tags: woocommerce,ecommerce,images,thumbnails
Requires at least: 4.5.0
Requires PHP: 5.6
Tested up to: 5.4.2
WC requires at least: 3.0.0
WC tested up to: 4.2.0
License: GNU General Public License v2.0
License URI: http://www.gnu.org/licenses/gpl-2.0.html

By default, WooCommerce will only show thumbnails for categories if the category has a thumbnail set.

This plugin changes this behaviour so that WooCommerce will hunt for a product thumbnail in the category being displayed, and use that instead.

== Description ==
Upon activation, the plugin will configure itself with the basic settings. Any categories that you have on your WooCommerce store that do not have a thumbnail set will then start displaying any available product thumbnails inside the category.

There are a number of options, these can be found in WooCommerce -> Settings -> Products -> Auto Category Thumbnails.

- Thumbnail Size sets the image size that the plugin should use when displaying the thumbnails. Defaults to shop_thumbail, but shop_catalog may be a better option for some themes.

- Go into Child Categories makes the plugin look in child categories too, useful if you have products buried in quite a deep category structure.

- Random Thumbnail tells the plugin to pick a random product thumbnail from those it finds every time the page loads - otherwise it always uses the first one it finds.

== Changelog ==
= 1.2.1 (24/02/2019) =

* Updated the notice shown if WooCommerce is deactivated to include the plugin name

= 1.2 (27/03/2018) =

* I'll be honest, I can't actually remember what changed here and I didn't start tracking on version control until after this!

= 1.1 (23/03/2018) =

* Added option to set the image size used for the automatic thumbnails.

= 1.0 (05/10/2017) =

* Initial release.

== License ==
Licensed under the [GNU General Public License v2.0](http://www.gnu.org/licenses/gpl-2.0.html)
