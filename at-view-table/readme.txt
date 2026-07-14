=== AT View Table ===
Contributors: francescodicandia
Tags: airtable, table, shortcode, data, admin
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display Airtable table data in a WordPress table view using an explicit shortcode.

== Description ==

AT View Table is a utility plugin that lets you render Airtable table data inside WordPress using a shortcode.

The plugin is designed for explicit control:

* connect WordPress to one Airtable base using a Base ID and Personal Access Token
* inspect a table schema from the WordPress admin area
* choose exactly which fields to render
* optionally filter, sort, and limit rows in the shortcode

Example shortcode:

`[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" filter_field="Status" filter_value="Active" sort_field="Name" sort_direction="asc" limit="25"]`

== Installation ==

1. Upload the `at-view-table` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Go to `Settings > AT View Table`.
4. Enter your Airtable Base ID.
5. Enter your Airtable Personal Access Token.
6. Optionally set a default row limit.
7. Use the `Inspect Table` tool to discover the exact field names for a table.
8. Add the shortcode to a page or post.

== Frequently Asked Questions ==

= Where do I find my Airtable Base ID? =

Use Airtable's ID guide from the plugin settings page, or see Airtable's official documentation for finding Airtable IDs.

= Where do I find my Airtable Personal Access Token? =

Create or manage your Airtable Personal Access Tokens from Airtable's token management page.

= Is `table_id` required? =

Yes. The shortcode is explicit and requires `table_id` and `fields`.

= Is `fields` required? =

Yes. You must provide a comma-separated list of Airtable field names to display.

== Screenshots ==

1. Settings screen with Airtable connection fields and default limit.
2. Inspect Table tool for discovering field names and types.
3. Example shortcode usage and table output.

== Changelog ==

= 0.1.0 =
* Initial public version of AT View Table.
* Airtable connection via Base ID and Personal Access Token.
* Explicit shortcode with required table and field selection.
* Optional filter, sort, and limit parameters.
* Manual Inspect Table admin tool.
