=== WooCommerce Product Filters ===
Contributors: barn2media
Tags: woocommerce, filters
Requires at least: 5.0
Tested up to: 6.2
Requires PHP: 7.4
Stable tag: 1.3.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Help customers to find what they want quickly and easily. Add product filters for price, color, category, size, attributes, and more.

== Description ==

Help customers to find what they want quickly and easily. Add product filters for price, color, category, size, attributes, and more.

== Installation ==

Please refer to [our support page](https://barn2.com/support-center/).

== Frequently Asked Questions ==

Please refer to [our support page](https://barn2.com/support-center/).

== Changelog ==

= 1.3.1 =
Release date 18 May 2023

 * Tweak: Avoid usage of reserved terms when generating slugs for filters.

= 1.3.0 =
Release date 16 May 2023

 * New: Added "Search" filter type.

= 1.2.2 =
Release date 09 May 2023

 * Fix: warning message when activating the plugin while WooCommerce is disabled.
 * Fix: Compatibility issue in php 7.4

= 1.2.1 =
Release date 03 May 2023

 * Fix: Compatibility issue in php 7.4

= 1.2.0 =
Release date 01 May 2023

 * New: Added ability to duplicate groups.
 * Fix: Scrollbar disappears when clicking on the "sort" dropdown while the filter is inside an horizontal group.
 * Fix: Deprecation notices in PHP 8.1

<!--more-->

= 1.1.14 =
Release date 18 April 2023

 * Tweak: Reworked scrolling to loop logic.
 * Fix: Divi - filtering caused the page to scroll up to the very top of the layout.
 * Fix: Easy Post Types and Fields - unable to use custom fields if the site had no custom taxonomies or attributes.

= 1.1.13 =
Release date 12 April 2023

* Fix: Category filter is showing all terms when on archive pages.
* Fix: Elementor Pro sort dropdown returns no products.
* Tweak: Updated js dependencies.
* Tweak: Updated internal libraries.

= 1.1.12 =
Release date 03 April 2023

* Fix: compatibility issue with WP 6.2 causing the React app to crash in certain situations.
* Dev: Tested up to WP 6.2

= 1.1.11 =
Release date 13 March 2023

* Fix: Elementor Pro - pagination not working when the shop page has been customized.
* Fix: Elementor Pro - clicking "clear filters" was not resetting filters.
* Fix: certain strings could not be translated.
* Tweak: updated language files.

= 1.1.10 =
Release date 07 February 2023

* Tweak: hide filters in archive pages when no products are found.
* Fix: pagination would return a json response via Elementor on archive pages.

= 1.1.9 =
Release date 01 February 2023

* Fix: compatibility issue between the WooCoomerce Product Table plugin and the Avada theme.
* Fix: total results count was sometimes wrong when queried through Elementor.

= 1.1.8 =
Release date 06 January 2023

* Tweak: Display 'Clear filters' link when only 1 filter is selected.
* Fix: filters not working correctly on archive pages with the Avada theme.
* Fix: filters not working correctly on archive pages with the Divi theme.

= 1.1.7 =
Release date 04 January 2023

* Fix: Styling of dropdowns in Flatsome theme.
* Fix: conflict with the WooCommerce Local Pickup plugin.
* Fix: various issues with the Elementor Pro theme builder on archive pages.

= 1.1.6 =
Release date 12 December 2022

* Fix: ACF Range slider not firing the appropriate database query.
* Fix: Toggling filter visibility inside the mobile drawer would hide the entire filter.
* Fix: ACF True/False filter not showing the initial count of products on first page load.
* Fix: ACF True/False filter not updating the total number of possible choices when using other filters.
* Fix: ACF True/False filter would display as "1" when inside the list of active filters.
* Dev: updated internal libraries.

= 1.1.5 =
Release date 06 December 2022

* Tweak: assigned a fixed max height to popovers when using filters with the horizontal layout.
* Fix: conflict with GeneratePress WooCommerce module.
* Fix: styling issues with the Flatsome theme.
* Fix: filtering products on archive pages would return all the products when using the plugin "Show Single Variations by Iconic".
* Fix: total results count would sometimes default to "0".
* Fix: php warning when using using the Theme Editor via Elementor Pro.
* Fix: compatibility hooks for WooCommerce shortcodes would not fire if the page did not use the `products` shortcode.
* Fix: "Custom fields" under the "Filter by" dropdown in the filters editor not visible when no custom taxonomies are found.
* Fix: Page preview parameters removed while filtering during preview of a page.

= 1.1.4 =
Release date 28 November 2022

* Fix: search results count shows the number of all products when no results are found.
* Fix: widget toggle hides the whole filter on click.
* Fix: filters not showing on shop and archive pages in Divi when the Products module "Products view type" setting is anything other than "Default".
* Tweak: added a filter to include support for the "Uncategorized" product category in filters.
* Tweak: added support for featured, sale, best selling & top rated products when using the WooCommerce `products` shortcode.

= 1.1.3 =
Release date 23 November 2022

* Fix: missing scoped dependency files.

= 1.1.2 =
Release date 23 November 2022

* Tweak: automatically generate numeric slugs when duplicates are found for filters with the same name.
* Tweak: index parent variable product id when indexing variations.
* Fix: missing scoped dependency when using the `Str` helper class.
* Fix: inability to index attributes of variations in certain situations.
* Fix: WPT Integration - counters of possible choices not taking variations into consideration when the table displays variations on separate rows.
* Fix: WPT Integration - counters of possible choices would wrongfully include the parent variable product during prefilling & when the table displays variations on separate rows.

= 1.1.1 =
Release date 14 November 2022

* Fix: slide out panel not working when using custom fields as filters on a custom WordPress page.

= 1.1.0 =
Release date 14 November 2022

* New: added support for filtering of products via "ACF" custom fields.
* New: added support for filtering of products via "Easy Post Types and Fields" custom fields.
* New: added "range slider" filter type.
* New: added support for categories and sub-categories as separate hierarchical dropdowns.
* New: added support for custom taxonomies as separate hierarchical dropdowns.
* Tweak: do not index out of stock products.
* Tweak: updated internal js libraries.

= 1.0.11 =
Release date 08 November 2022

* New: added compatibility with the Divi theme
* Fix: results not showing in taxonomy pages in WordPress 6.1
* Tweak: force default values when saving options via the settings panel

= 1.0.10 =
Release date 03 November 2022

 * Fix: results not showing in WordPress 6.1

= 1.0.9 =
Release date 24 October 2022

 * Fix: Compatiblity issue with Kadence Theme
 * Fix: Dropdown filters are not working on mobile in Jupiter theme
 * Tweak: attributes in filters now uses the "Default sort order" setting for sorting options in filters.

= 1.0.8 =
Release date 20 October 2022

 * Fix: setup wizard "filter visibility" and "filter behavior" not displaying inputs in certain situations.
 * Fix: crash of product tables powered by WooCommerce Product Table when tables had certain settings.
 * Fix: terms in color checkboxes and images filters not sorted based on their order in the admin area.
 * Dev: updated internal libraries.

= 1.0.7 =
Release date 06 October 2022

 * Tweak: adjusted the logic of selectable choices for taxonomies and attributes types of filters when used on a taxonomy page.
 * Tweak: adjusted the logic of selectable choices for taxonomies and attributes types of filters when used with the WooCommerce Product Table plugin.
 * Fix: license activation & plugin updates not working properly.
 * Fix: popover can't be closed on mobile.
 * Fix: _paged parameter ignored when no filters selected.
 * Fix: popover mispacled during chunk download.
 * Fix: terms in filters not sorted based on their order in the admin area.

= 1.0.6 =
Release date 27 September 2022

 * Tweak: sanitize non-latin characters for filters slugs.
 * Fix: empty "div" tag causing extra spacing in certain situations.

= 1.0.5 =
Release date 22 September 2022

 * Added: integration with the "WooCommerce Show Single Variations by Iconic" plugin.
 * Fix: pagination not working correctly in certain situations.
 * Fix: missing textdomain for certain strings.
 * Tweak: adjusted plugin activation process.
 * Dev: updated language files.
 * Dev: added a series of new hooks and filters.

= 1.0.4 =
Release date 15 September 2022

 * Fix: `permission_callback` parameter for pricing api.
 * Tweak: fallback to highest product price in the store when retrieving pricing details on taxonomy pages and the query produces no results.
 * Tweak: no longer check for catalog visibility when retrieving pricing details on taxonomy pages.
 * Dev: Tested up to WooCommerce 6.9

= 1.0.3 =
Release date 05 September 2022

 * Fix: price range slider not showing the taxonomy term specific max price.
 * Fix: setup wizard firing database queries when not needed.
 * Fix: svg overflow wasn't applied correctly.

= 1.0.2 =
Release date 24 August 2022

 * Fix: attributes filter checkboxes using the `AND` logic instead of `OR`.
 * Tweak: removed unused imports from js assets.
 * Tweak: moved code shared by the Attribute and Taxonomy models to a trait.
 * Tweak: removed code no longer needed.
 * Tweak: adjusted redundancy of the `get_search_query` method for certain models.

= 1.0.1 =
Release date 11 August 2022

 * Fix: popover body hidden underneath other elements in certain situations.
 * Fix: issue with columns counts & resizing on mobile devices for horizontal filters.
 * Fix: prevent crashing of pricing filter when products have been imported and not yet indexed.
 * Fix: the unique sources validation logic would wrongly fire on the "all attributes" filter when saving groups.
 * Tweak: automatically index products after import via CSV.
 * Tweak: reduced the code required to calculate valid choices for checkboxes filters.
 * Tweak: adjusted alignment of labels for checkboxes and radio filters when the label was too long.
 * Tweak: reset pagination to 1st page when changing sorting order.
 * Tweak: minor layout adjustments to the filters editor.
 * Dev: Tested up to WooCommerce 6.8.0

= 1.0 =

* Initial release.
