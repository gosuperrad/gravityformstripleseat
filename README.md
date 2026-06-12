# Gravity Forms Tripleseat Add-On

Send [Gravity Forms](https://www.gravityforms.com/) submissions straight to the
[Tripleseat](https://tripleseat.com/) Lead API — with per-form field mapping and
built-in UTM / GCLID campaign tracking.

Built on the Gravity Forms **Feed Add-On Framework**, so it behaves like the feed
add-ons you already know: configure the account once, then add a feed per form.

---

## Features

- **Account key stored once** — your Tripleseat public key lives in the global add-on
  settings, not in every form.
- **Per-form feeds** — one or more feeds per form, each targeting a specific Tripleseat
  lead form by ID.
- **Full field mapping** — name, email, phone, company, event description, event date,
  start/end time, guest count, location ID, and additional information.
- **First-class UTM / GCLID capture** — persists `utm_source`, `utm_medium`,
  `utm_campaign`, `utm_term`, `utm_content`, and `gclid` across page views (first-party
  cookie, last-touch, 30 days) and feeds them into Tripleseat's `campaign_*` / `gclid`
  lead fields.
- **Feed conditions & duplication** plus full debug logging via Gravity Forms.
- **Optional future-only dates** — add the `gf-future-date` CSS class to any Date field
  to block past dates (client + server side) using Gravity Forms' native datepicker
  filter. No CDN assets, no hardcoded field IDs.

## Requirements

| | |
| --- | --- |
| WordPress | 6.0+ |
| PHP | 8.0+ |
| Gravity Forms | 2.4+ |

## Installation

### Via Composer (recommended)

This plugin is distributed through its GitHub repository rather than Packagist. Add the
VCS repository to your project's `composer.json`:

```jsonc
"repositories": [
    { "type": "vcs", "url": "https://github.com/gosuperrad/gravityformstripleseat" }
]
```

Then require it:

```bash
composer require superrad/gravityformstripleseat:^1.0
```

[`composer/installers`](https://github.com/composer/installers) places the plugin in the
correct directory automatically:

- **Bedrock:** `web/app/plugins/gravityformstripleseat/`
- **Standard WordPress:** `wp-content/plugins/gravityformstripleseat/`

To track the main development branch instead of stable releases, require `dev-main`
rather than `^1.0`. The repository is public, so no authentication is required.

### Manual

Download `gravityformstripleseat.zip` from the
[latest release](https://github.com/gosuperrad/gravityformstripleseat/releases/latest),
then upload it via **Plugins → Add New → Upload Plugin**, or extract it into
`wp-content/plugins/`.

## Quick start

1. Install and activate Gravity Forms (2.4+) and this add-on.
2. **Forms → Settings → Tripleseat** → paste your Tripleseat **public key** (found next
   to the lead form embed code in Tripleseat under **Settings → Lead Forms**) → Update.
   This is account-wide; you only do it once.
3. Edit a form → **Settings → Tripleseat** → **Add New** feed:
   - Enter the **Lead Form ID** of the Tripleseat lead form that should receive
     submissions.
   - Map your form fields to the Tripleseat lead fields.
4. Submit a test entry and confirm the lead appears in Tripleseat.

For a full walkthrough — including the location selector and UTM / GCLID setup — see
[`docs/form-setup.md`](docs/form-setup.md).

## Capturing UTM / GCLID campaign data

The add-on automatically reads `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`,
`utm_content`, and `gclid` from the page URL and stores them in a first-party cookie
(last-touch, 30 days), so the values survive even if the visitor browses several pages
before reaching the form.

To pass them to Tripleseat:

1. Add six **Hidden** fields to your form.
2. For each, enable **Allow field to be populated dynamically** and set the parameter
   name to one of: `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`,
   `utm_content`, `gclid`.
3. In the form's Tripleseat feed, under **Campaign / UTM Fields**, map each hidden field
   to its matching Tripleseat campaign field.

On submission the campaign values appear on the Tripleseat Lead Details page and in
reporting. Empty values are omitted from the request.

## Development

```bash
composer install      # install dev tooling (PHPCS, WPCS, PHPUnit)
composer lint         # run phpcs
composer lint:fix     # run phpcbf
composer test         # run phpunit
```

### Releasing

Releases are built automatically by
[`.github/workflows/release.yml`](.github/workflows/release.yml). Push a version tag and
the workflow packages a clean plugin zip and attaches it to a GitHub Release:

```bash
git tag -a v1.1.0 -m "v1.1.0"
git push origin v1.1.0
```

Keep the version in sync across `gravityformstripleseat.php` (header + `GF_TRIPLESEAT_VERSION`),
`readme.txt` (`Stable tag`), and the git tag.

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html) © [Super Rad](https://gosuperrad.com/)
