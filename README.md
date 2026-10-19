# Ajax Add To Cart for Zen Cart

**Platform:** Zen Cart 2.x  


> **Disclaimer:** Please note that this plugin is installed and used at your own risk. If you are unsure of how to complete the installation procedure documented below, please seek support in the official Forum section (keep in mind Forums are public and work on voluntary basis) or contact me for paid assistance. ZenExpert takes no responsibility for problems related to the installation or use of this plugin.

Ajax Add To Cart is a fully encapsulated, observer-based plugin for Zen Cart that intercepts standard product additions and performs them asynchronously. This prevents the standard page reload, vastly improving user experience. It elegantly handles everything from single product additions to complex multiple-product quantity boxes and checkboxes.

## Table of Contents
- [Installation](#installation)
- [Template Compatibility](#template-compatibility)
- [Admin-Configured Options](#admin-configured-options)
- [Features & Usage](#features--usage)
  - [Interactive Modal Popup](#interactive-modal-popup)
  - [Dynamic Sidebox Updates](#dynamic-sidebox-updates)
  - [Header Cart & Checkout Links](#header-cart--checkout-links)
  - [In-Cart Quantity Updates](#in-cart-quantity-updates)
  - [Developer Template Integrations](#developer-template-integrations)
- [Issues](#common-issues)
- [Contributing](#contributing)
- [License](#license)

## Installation

This plugin uses strict encapsulation and reference-based observers, meaning it requires zero core file modifications.

1. Backup your database.
2. Upload the plugin folder to your store's `zc_plugins` directory.
3. Navigate to your Zen Cart Admin and go to **Plugins > Plugin Manager**.
4. Locate Ajax Add To Cart and click **Install**. The necessary database configurations will automatically be installed.
5. Configure the plugin parameters via the newly generated menu under **Configuration > Ajax Add To Cart**.

## Template Compatibility

This plugin is highly adaptable and fundamentally template-agnostic. It has been rigorously tested and confirmed fully compatible with standard Zen Cart templates, including **Responsive Classic** and **ZCA Bootstrap**.

The plugin dynamically injects its own cross-browser compatible DOM elements (avoiding dependencies on external modal libraries) and includes CSS that can be effortlessly overridden by simply placing a custom `ajax_add_to_cart.css` file into your active template's CSS folder.

## Admin-Configured Options

Once installed, you can precisely control the plugin's frontend behavior via the admin configuration panel without touching any code:

*   **Enable Ajax Add To Cart:** A master switch to enable or disable the Ajax Add To Cart functionality globally.
*   **Form Selector:** Define the jQuery selector used to target add-to-cart forms (default targets standard product, buy now, and multiple-product forms).
*   **Button Selector:** Define the jQuery selector for the submit buttons inside the targeted forms.
*   **Link Selector:** Define the jQuery selector used to intercept direct "Buy Now" links.
*   **Cart Count Selector:** The jQuery selector for on-page elements displaying the total cart item count.
*   **Cart Total Selector:** The jQuery selector for on-page elements displaying the total financial cart amount.
*   **Sidebox Selector:** The jQuery selector for the shopping cart sidebox wrapper (defaults to `#shopping-cart, #shoppingcart`).
*   **In-Cart Selector:** The jQuery selector for the "Quantity in Cart" element displayed natively on product info pages (defaults to `.cartInCart, #cartAdd p`).
*   **In-Cart Parent:** The jQuery selector for the parent wrapper where the Quantity in Cart element should be injected if it does not already exist on the page.
*   **Inject Header Cart Links:** When enabled, Shopping Cart and Checkout links taken from the active template's `tpl_header.php` are injected after an Ajax add if the empty-cart header omitted them.
*   **Header Nav Selector:** The jQuery selector for the header navigation list where those links should be inserted (defaults to `#navMain ul, ul.navbar-nav`).
*   **Header Cart Link Selector:** The jQuery selector used to detect whether the header already contains a Shopping Cart link. If a match is found, the plugin does nothing.

## Features & Usage

### Interactive Modal Popup
Upon a successful cart addition, the native page redirect is halted and a clean, responsive modal popup is displayed to the user.
*   Parses exact product names (including multiple products added simultaneously) into a bolded success message.
*   Displays real-time cart subtotal and item count directly inside the modal.
*   Provides frictionless "Continue Shopping" and "View Cart" action buttons.
*   Automatically falls back to a graceful native form submission if the product form requires a file upload.

### Dynamic Sidebox Updates
The plugin natively renders Zen Cart's `shopping_cart.php` sidebox module in the background and returns the updated HTML.
*   Queries the `layout_boxes` database table to determine if the sidebox is assigned to the left or right column.
*   Retrieves the exact sort order of all active sideboxes in the assigned column.
*   Intelligently injects the sidebox HTML into the correct DOM position relative to its neighboring sideboxes, even if the template previously hid the column because the cart was empty.

### Header Cart & Checkout Links
Most Zen Cart templates hide the header Shopping Cart and Checkout links while the cart is empty. After an Ajax add those links would otherwise stay missing until a full page reload.
*   Reads the store's **active** `tpl_header.php` (the same file the template is already using, including overrides) and extracts the cart-gated markup — it does not invent its own links.
*   Chooses the matching mobile / tablet / desktop fragment when a responsive header contains more than one copy of the links.
*   Injects that native HTML into the header navigation only when the links are not already in the DOM.
*   After a full page reload the template renders the links itself, and the plugin steps aside.

### In-Cart Quantity Updates
The plugin completely replicates Zen Cart's native logic for displaying a customer's active product quantity on the `product_info` page.
*   Checks the `zen_get_show_product_switch()` configuration to ensure the quantity text should be displayed.
*   Sums up all attribute variations natively using the cart's `in_cart_mixed()` method.
*   Gracefully replaces the existing quantity text on the page, or securely prepends it to the configured parent container if it doesn't currently exist.

### Developer Template Integrations
If you are building custom templates or complex sliding mini-carts, the plugin provides explicit hooks to integrate your own UI elements seamlessly:
*   **PHP Interception:** The observer triggers the `NOTIFY_AJAX_ADD_TO_CART_FILTER_RESPONSE` hook immediately before JSON encoding. This allows secondary add-on plugins to inject custom HTML payloads directly into the response array.
*   **JavaScript Broadcasting:** The `handleAjaxResponse` function fires an `ajaxAddToCartSuccess` document event, broadcasting the JSON response and the originating form data. This allows any custom template script to intercept the event and manipulate the DOM.
*   **Modal Suppression:** Custom template scripts can disable the default modal popup entirely by setting `response.disableModal` to true within the broadcasted event.

## Common Issues
None reported so far.


## Sources and Credits
* External libraries used in this plugin: none


## Contributing
Found a bug? Feel free to submit an issue or pull request.


## License
GNU Public License V2.0
