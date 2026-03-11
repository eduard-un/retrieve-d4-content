# DT Retrieve Divi 4 Content

A WordPress admin plugin that retrieves the Divi 4 layout stored in `wp_postmeta` under the meta key `_et_pb_divi_4_content`.

## What it does

### Tools page

- Adds an admin page under **Tools → Divi 4 Content**.
- Lets an admin enter a **Post/Page ID** and retrieve the layout by ID.

### Metabox

- Adds a **Divi 4 Layout** metabox to every public post type (pages, posts, etc.).
- The metabox appears on each post/page edit screen and automatically displays the stored layout for that post — no need to look up the ID manually.

### Shared features (both contexts)

- Displays the raw `meta_value` in a syntax-highlighted, dark-themed code viewer (CodeMirror).
- **Copy to clipboard** — copies the raw layout content.
- **Save As JSON** — downloads a `.json` file structured for Divi import:
  ```json
  {
    "context": "et_builder",
    "data": { "<post_id>": "<meta_value>" },
    "presets": {},
    "global_colors": [],
    "images": [],
    "thumbnails": []
  }
  ```
- If no `_et_pb_divi_4_content` meta exists for the post, a clear message is shown instead.

## How it works (technical)

- **Tools page** — implemented via `add_management_page()`. On submit it validates permissions (`manage_options`), the nonce, and the Post/Page ID, then uses a prepared query (`$wpdb->prepare()`) to fetch the `meta_value`.
- **Metabox** — registered via `add_meta_box()` on all public post types. The current post's `_et_pb_divi_4_content` is fetched automatically using `$post->ID`.
- Uses WordPress' built-in CodeMirror integration via `wp_enqueue_code_editor()`.
  - If CodeMirror is available, the textarea is enhanced into a read-only code editor with a dark theme.
  - If CodeMirror is not available, it falls back to a styled readonly textarea.

## Installation

1. Copy this plugin folder to:

   `wp-content/plugins/dt-retrieve-d4-content/`

2. In WP Admin, go to:

   **Plugins → Installed Plugins**

3. Activate:

   **DT Retrieve Divi 4 Content**

## Usage

### Via the Tools page

1. Go to **Tools → Divi 4 Content**.
2. Enter the Post/Page ID.
3. Click **Retrieve**.
4. Use **Copy to clipboard** or **Save As JSON**.

### Via the Metabox

1. Edit any page or post.
2. Scroll down to the **Divi 4 Layout** metabox.
3. The layout content is displayed automatically.
4. Use **Copy to clipboard** or **Save As JSON**.

## Files

- `dt-retrieve-d4-content.php` — Main plugin file (admin page, metabox, and lookup logic).
- `assets/admin.css` — Admin UI styling, metabox styles, and CodeMirror dark theme.
- `assets/admin.js` — Clipboard copy, Save As JSON download, and CodeMirror initialization.

## Notes

- The `_et_pb_divi_4_content` value can be large.
- Admin pages may cache CSS/JS depending on your setup.
  - If you don't see styling changes immediately, do a hard refresh.

## License

MIT (or your preferred license)
