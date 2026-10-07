# Put Share Buttons on Any Page

Goal: show the share buttons for a product somewhere other than its own product page, such as a landing page, blog post or sidebar.

## Steps

1. Find the product's ID. Go to **Products > All Products** and hover over the product name. The ID is shown under the name ("ID: 123").
2. Edit the page or post where you want the buttons.
3. Add a **Shortcode** block (or paste into the classic editor) with:

```
[wog_social_share id="123"]
```

Replace 123 with your product's ID.
4. Click **Update**.

On a product page itself you can use `[wog_social_share]` with no ID. It uses the product being viewed.

## What to know

- The only option is `id`.
- The buttons follow your **Button Style** and the network switches. They show even if **Enable Share Buttons** is off, so you can use the shortcode instead of automatic placement.
- Nothing is shown for a product that is a draft, private, or password protected. The shortcode never shares something visitors cannot see.
- Nothing is shown for a product whose **Enable social sharing output for this product** box is unticked.
- With no valid product, the page gets only a hidden HTML comment.

## How to check it worked

View the page. The "Share this product:" row appears where you put the shortcode.

## If it doesn't work

See [Share button problems](../troubleshooting/share-button-problems.md).
