# Check How a Product Looks When Shared

Goal: see what Facebook, LinkedIn and Google read from your product page, and refresh a network's old copy after you change something.

## Check a product

1. Go to **WB Plugins > Open Graph**. The **Overview** tab has a **Testing Tools** box.
2. Copy the full link of a product from your store (the address in the browser).
3. Click the tool you need, then paste the link:

| Tool | Use it to check |
|---|---|
| **Facebook Debugger** | The Facebook preview: title, image, description and price. |
| **LinkedIn Inspector** | The LinkedIn preview |
| **Google Rich Results** | Your structured data: logo, breadcrumbs, product details |

For X (Twitter), the plugin no longer links a validator because X retired its card validator. To see the preview, start a new post on X and paste the product link. X usually shows the card before you send it.

## Refresh an old preview

Networks keep a copy of your page for days. After you change a title, description, price or image:

1. Open the Facebook Debugger or LinkedIn Inspector from the **Overview** tab.
2. Paste the product link and fetch it again. In the Facebook Debugger, use the button that fetches new information ("Scrape Again").
3. If you use a caching plugin or a CDN, clear its cache first, otherwise the network re-reads the old page.

## See the tags yourself

1. Open the product page in your browser.
2. Right-click and choose **View Page Source**.
3. Search for `Woo Open Graph Meta Tags`. The tags are between that comment and `End Woo Open Graph Meta Tags`.

## How to check it worked

The preview shows the title, image, description and price you expect. After editing a product, the networks show the new version once you have refreshed them.

## If it doesn't work

See [Social preview problems](../troubleshooting/social-preview-problems.md).
