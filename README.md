# Letsby Avenue website

WordPress theme and page content for https://letsby-avenue.co.uk, hosted on Krystal.

- `theme/letsby/` — the theme (design, SEO tags, schema, enquiry form, cookie consent, redirects)
- `content/` — page content (WordPress block markup) and `pages.json` (titles, SEO titles, descriptions)
- `content/guides/*.txt` — landlord guides in a simple text format; run `python3 tools/build_guides.py` to rebuild the HTML
- `.cpanel.yml` — cPanel deploys the theme and content into the site on every pull

After a deploy, run **Tools › Letsby setup** in WordPress to download fonts/images and create or update the pages.

Business details (phone, address, company number, redress scheme) live in one place: `letsby_business()` in `theme/letsby/functions.php`.
Old-URL redirects live in `theme/letsby/inc/redirects.php`.
