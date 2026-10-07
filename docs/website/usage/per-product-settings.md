# Per-Product Settings

Each product has a Social Media Settings box on its edit screen where you can
override what the plugin outputs for that product.

## The Social Media Settings box

The box appears in the main column of the product editor and contains:

- Enable social media optimization for this product. A checkbox, on by
  default. Unchecking it turns off the plugin's Open Graph output for that
  product.
- Social Media Title. A custom title used for social sharing. Leave empty to
  use the product name. Limited to 60 characters, with a live counter.
- Social Media Description. A custom description used for social sharing. Leave
  empty to use the product description. Limited to 155 characters, with a live
  counter.
- Social Media Image. A read-only indicator that shows whether the product has
  a featured image (which will be used) or warns when none is set.

The title and description fields show the product's name and description as
placeholder text, so you can see the automatic values you would get if you left
them empty.

## What gets stored

| Field | Product meta key |
|---|---|
| Custom title | `_wog_og_title` |
| Custom description | `_wog_og_description` |
| Optimization disabled | `_wog_disable_og` |

The disable flag uses inverted logic: the meta key is set only when the box is
unchecked.

## Products list column

The product list table in wp-admin gains a Social column with a colored dot
per product:

- Green: using automatic social media content
- Amber: has a custom title or description
- Red: social media optimization disabled

## Interaction with the sitemap

Saving a product refreshes cached product data and schedules a sitemap
regeneration, so changes to titles, descriptions, images, and stock show up in
the sitemap within about ten minutes.
