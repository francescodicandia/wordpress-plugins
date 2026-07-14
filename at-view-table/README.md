# AT View Table

AT View Table is a WordPress utility plugin for rendering Airtable data in a table view.

## What It Does

The plugin lets you:

- connect WordPress to an Airtable base using a Base ID and Personal Access Token
- inspect a table schema from the WordPress admin area
- render specific Airtable columns with a shortcode
- optionally filter, sort, and limit rows

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Airtable Personal Access Token with read access to the base
- Airtable Meta API/schema access for field discovery and table inspection

## Installation

1. Copy the `at-view-table` folder into `/wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Go to `Settings > AT View Table`.
4. Enter:
   - `Airtable Base ID`
   - `Airtable API Token`
5. Optionally set the global default limit.
6. Use `Inspect Table` to find the exact field names for a table.
7. Add the shortcode to a page or post.

## Shortcode

```text
[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status"]
```

### Supported shortcode attributes

| Attribute | Required | Description |
|-----------|----------|-------------|
| `table_id` | Yes | Airtable Table ID to query |
| `fields` | Yes | Comma-separated Airtable field names to render |
| `filter_field` | No | Field used for simple equality filtering |
| `filter_value` | No | Value used with `filter_field` |
| `sort_field` | No | Field used for sorting |
| `sort_direction` | No | `asc` or `desc` |
| `limit` | No | Maximum number of rows to fetch from Airtable |
| `page_size` | No | Rows per page for client-side pagination (defaults to `limit`) |

### Examples

```text
[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Email,Status"]
[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" filter_field="Status" filter_value="Active"]
[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" sort_field="Name" sort_direction="asc"]
[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" limit="25"]
[at_view_table table_id="tblXXXXXXXXXXXXXX" fields="Name,Status" limit="100" page_size="25"]
```

## Notes

- `table_id` and `fields` are required.
- If `limit` is omitted, the plugin uses the global default limit from settings.
- If `page_size` is omitted, no pagination controls are shown (all rows on one page).
- Sorting and pagination are client-side (JavaScript). All rows up to `limit` are loaded in the page.
- The plugin uses the Airtable Meta API to inspect table fields.

## License

This plugin is distributed under **GPL-2.0-or-later**.
