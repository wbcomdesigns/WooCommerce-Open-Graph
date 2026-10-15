# Share Buttons

Share buttons let shoppers send a product to their friends in one click. They appear on the product page under the label "Share this product:".

## The buttons

| Button | What happens when a shopper clicks it |
|---|---|
| Facebook | Opens Facebook's share window with your product link. Facebook then reads the preview from your page. |
| Twitter | Opens the X post window with the product name, a short description (when there is room) and the link. |
| LinkedIn | Opens LinkedIn's share window with your product link. |
| Pinterest | Opens Pinterest's pin window with the product link, the product image and a description. |
| WhatsApp | Opens WhatsApp with the product name and link ready to send. |
| Email | Opens the shopper's email app with a subject "Check out: product name" and a message containing the name, description and link. Off by default. |
| Copy | Copies the product link. The button briefly shows "Link copied!". Always shown. |

A network's button appears only when its switch is on in **General > Social Platforms**. The Email button has its own switch in **Share Buttons > Email Button**.

## Share text

The description in the share text is the short description (or the long one if there is no short one), followed by the price, cut to 200 characters. For variable and grouped products whose prices differ, the price is a range such as "$42.00 - $45.00".

## Styles

| Style | Look |
|---|---|
| Modern | Clean buttons with smooth hover animation (default) |
| Classic | Traditional rectangular buttons |
| Minimal | Icon-only buttons with no text labels |

## Positions

| Position | Where the buttons appear |
|---|---|
| After Add to Cart Button | Right under the Add to Cart button (default) |
| Before Add to Cart Button | Right above the Add to Cart button |
| After Product Summary | Below the main product area, before the tabs |
| After Product Tabs | Below the description and reviews tabs |

Sold-out products have no Add to Cart button. For the two Add to Cart positions, the buttons then appear just after the product summary block, so sold-out products still show them.

## Rules

- The buttons appear on single product pages only (unless you use the [shortcode](../how-to/share-buttons-with-shortcode.md)).
- If you untick **Enable social sharing output for this product** on a product, its buttons disappear too.
- The share script and styles load on a product page only when its buttons will show.
- Buttons use WooCommerce's standard product page positions. A theme or page builder that builds the product page without those positions will not show them. Use the shortcode instead.

## Add them anywhere

`[wog_social_share id="123"]` shows the buttons for product 123 on any page or post. See [Put Share Buttons on Any Page](../how-to/share-buttons-with-shortcode.md).
