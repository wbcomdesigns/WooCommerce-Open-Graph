# Social Share Buttons

When share buttons are enabled, the plugin adds a share row to single product
pages so shoppers can share the product on their own networks. The button CSS
and JavaScript load only on product pages.

## Platforms

The share row includes a button for each enabled platform, plus a copy-link
button. The available platforms are:

- Facebook
- X (Twitter)
- LinkedIn
- Pinterest
- WhatsApp
- Email
- Copy link (always shown, copies the product URL to the clipboard)

A platform button appears only when that platform is enabled in the core
settings. Each button links to the relevant share endpoint and opens in a new
tab.

## What each button shares

- Facebook and LinkedIn: share the product URL only. Those platforms read the
  product's Open Graph tags to build the preview.
- X (Twitter): shares the URL plus the product title, and appends the
  description when the title is short. Text is trimmed to fit.
- Pinterest: shares the URL, product image, and a description.
- WhatsApp: shares the product title and URL.
- Email: opens the mail client with a subject and body containing the title,
  description, and URL.

The product title, description, and image come from the product. The
description is cleaned of HTML and, when a price exists, has the price appended.

## Button styles

Three visual styles are available, chosen in the settings:

- Modern
- Classic
- Minimal

## Button position

The share row can be placed at one of four positions on the product page:

- After the Add to Cart button (default)
- Before the Add to Cart button
- After the product summary
- After the product tabs

## Placing buttons manually

You can render the share row anywhere with the `[wog_social_share]` shortcode.
See [Shortcodes](../developer-guide/shortcodes.md).

## Share tracking

When a share button is used, the front-end script can report the event to the
server, which fires the `wog_social_share_tracked` action so other code can
record it. The plugin itself does not store share counts.
