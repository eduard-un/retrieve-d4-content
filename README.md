# DT Retrieve Divi 4 Content

A small WordPress admin plugin that retrieves the Divi 4 layout stored in `wp_postmeta` under the meta key `_et_pb_divi_4_content` for a given Post/Page ID.

## What it does

- Adds an admin page under:
  - **Tools → Divi 4 Content**
- Lets an admin enter a **Post/Page ID**.
- Looks up the meta key `_et_pb_divi_4_content` in `wp_postmeta` for that ID.
  - If found:
    - Displays the raw `meta_value` in a readable, fixed-height, scrollable code viewer.
    - Auto-selects the full content on load.
    - Provides a **Copy to clipboard** button.
  - If not found:
    - Displays an error message:
      - `The ID requested doesn't have any Divi 4 layout in DB`

## How it works (technical)

- The plugin page is implemented as a **Tools** page using `add_management_page()`.
- On submit, it:
  - Validates permissions (`manage_options`).
  - Validates the nonce.
  - Validates the Post/Page ID.
  - Uses a prepared SQL query (via `$wpdb->prepare()`) to fetch `meta_value` from the `{$wpdb->postmeta}` table.
- For better readability in wp-admin, it uses WordPress’ built-in CodeMirror integration via `wp_enqueue_code_editor()`.
  - If CodeMirror is available, the result textarea is enhanced into a code editor view.
  - If CodeMirror is not available, it falls back to a styled readonly textarea.

## Installation

1. Copy this plugin folder to:

   `wp-content/plugins/dt-retrieve-d4-content/`

2. In WP Admin, go to:

   **Plugins → Installed Plugins**

3. Activate:

   **DT Retrieve Divi 4 Content**

## Usage

1. Go to:

   **Tools → Divi 4 Content**

2. Enter the Post/Page ID.
3. Click **Retrieve**.
4. The content will appear in the viewer (auto-selected).
5. Click **Copy to clipboard**.

## Files

- `dt-retrieve-d4-content.php` — Main plugin file (admin page + lookup logic).
- `assets/admin.css` — Admin UI styling + CodeMirror dark theme tweaks.
- `assets/admin.js` — Clipboard copy and CodeMirror initialization / auto-select behavior.

## Notes

- The `_et_pb_divi_4_content` value can be large.
- Admin pages may cache CSS/JS depending on your setup.
  - If you don’t see styling changes immediately, do a hard refresh.

## License

MIT (or your preferred license)
