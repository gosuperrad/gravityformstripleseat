=== Gravity Forms Tripleseat Add-On ===
Contributors: superrad
Tags: gravity forms, tripleseat, leads, crm, utm
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send Gravity Forms submissions to the Tripleseat Lead API, with per-form field mapping
and built-in UTM / GCLID campaign tracking.

== Description ==

This add-on connects Gravity Forms to Tripleseat using the Tripleseat Lead Form API. It
is built on the Gravity Forms Feed Add-On Framework, so it behaves like the other feed
add-ons you already know: configure the account once, then add a feed per form.

Features:

* Account public key stored once in the global add-on settings.
* One or more feeds per form, each targeting a specific Tripleseat lead form by ID.
* Field mapping for the standard Tripleseat lead fields (name, email, phone, company,
  event description, event date, start/end time, guest count, location ID, additional
  information).
* First-class UTM / GCLID capture: the add-on persists utm_source, utm_medium,
  utm_campaign, utm_term, utm_content and gclid across page views and feeds them into
  Tripleseat's campaign_source, campaign_medium, campaign_name, campaign_term,
  campaign_content and gclid lead fields.
* Feed conditions, feed duplication, and full debug logging via Gravity Forms.
* Optional future-only date restriction: add the `gf-future-date` CSS class to any Date
  field to block past dates (client + server side), using Gravity Forms' native
  datepicker filter — no CDN assets or hardcoded field IDs.

== Installation ==

1. Install and activate Gravity Forms (2.4+).
2. Install and activate this add-on.
3. Go to Forms → Settings → Tripleseat and enter your Tripleseat **public key** (found
   next to the lead form embed code in Tripleseat under Settings → Lead Forms).
4. Edit a form, open Settings → Tripleseat, and add a feed:
   * Enter the **Lead Form ID** of the Tripleseat lead form to receive submissions.
   * Map your form fields to the Tripleseat lead fields.

For a full walkthrough — including the location selector and UTM/GCLID setup — see
`docs/form-setup.md`.

== Capturing UTM / GCLID campaign data ==

The add-on automatically reads `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`,
`utm_content` and `gclid` from the page URL and stores them in a first-party cookie
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

== Changelog ==

= 1.0.0 =
* Initial release.
