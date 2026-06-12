# Setting up a form with the Tripleseat Add-On

This guide walks through connecting a Gravity Form to Tripleseat so submissions
create leads, including location selection and UTM / GCLID campaign tracking.

## Before you start

You need:

- **Gravity Forms** (2.4+) and this add-on active.
- Your Tripleseat **public key** (account-level).
- The **Lead Form ID** of the Tripleseat lead form that should receive submissions.
- The **location IDs** for any locations guests can choose (see below).

### Where to find these in Tripleseat

- **Public key & Lead Form ID** — open the lead form in Tripleseat and view its
  embed / API code. The snippet contains both, e.g.
  `…/leads/create.js?lead_form_id=12345&public_key=abc123…`. The `lead_form_id`
  value is your Lead Form ID; the `public_key` is your public key.
- **Location IDs** — these are not exposed through the public lead-form key. Find a
  location's numeric ID from its page in the Tripleseat admin (the ID typically
  appears in the location's URL), from the lead form's location configuration, or by
  asking Tripleseat support to confirm your location IDs. Record each location's
  **name → ID** before building the form.

## 1. Enter the public key (once)

**WP admin → Forms → Settings → Tripleseat** → paste your **Public Key** → Update.
Leave **API Endpoint** at its default. This is account-wide; you only do it once.

## 2. Build the form fields

Add the fields you want to collect. Typical lead fields and the Tripleseat target
they map to:

| Form field (example)   | Tripleseat lead field    |
| ---------------------- | ------------------------ |
| Name (First / Last)    | First Name / Last Name   |
| Email                  | Email Address (required) |
| Phone                  | Phone Number             |
| Company / Organization | Company                  |
| What's the occasion?   | Event Description        |
| Event date             | Event Date               |
| Start / End time       | Start Time / End Time    |
| Guest count            | Guest Count              |
| Location (see step 3)  | Location ID              |
| Notes / extra details  | Additional Information   |

Email is the only required mapping. Map only the fields you collect — unmapped or
empty values are simply omitted from the lead.

## 3. Set up the location selector

If guests choose a location, add a **Drop Down** (or Radio Buttons) field and set each
choice so the **value is the Tripleseat location ID** and the label is the location
name. In the Gravity Forms field editor, enable **"show values"** on the choices:

| Choice label (shown to guest) | Choice value (stored) |
| ----------------------------- | --------------------- |
| West Midtown Taproom          | 1001                  |
| Garage at Westside            | 1002                  |
| …                             | …                     |

Because the choice **value** is the location ID, mapping this field to **Location ID**
in the feed sends the correct ID to Tripleseat no matter which location is selected.

> Tip: keep this choice list in sync if you add/rename locations in Tripleseat, and
> double-check each value against the name → ID list you recorded earlier.

If a form is tied to a single location, you can skip the selector and instead use a
**Hidden** field whose default value is that location's ID, then map it to Location ID.

## 4. Add UTM / GCLID capture (optional but recommended)

The add-on automatically reads `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`,
`utm_content` and `gclid` from the page URL and remembers them in a first-party cookie
(last-touch, 30 days), so the values survive even if the guest browses a few pages
before reaching the form.

To pass them to Tripleseat:

1. Add six **Hidden** fields to the form.
2. For each, open its **Advanced** tab → enable **"Allow field to be populated
   dynamically"** → set the **Parameter Name** to one of:
   `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `gclid`.
3. You'll map these in the feed in the next step.

## 5. Create the Tripleseat feed

**Edit the form → Settings → Tripleseat → Add New**:

- **Feed Name** — anything to identify it.
- **Lead Form ID** — the Tripleseat lead form ID from the "Before you start" section.
- **Lead Fields** — map each form field to its Tripleseat lead field (step 2),
  including the **Location ID** mapping (step 3).
- **Campaign / UTM Fields** — map each hidden field to its matching Tripleseat
  campaign field:

  | Hidden field (param) | Tripleseat campaign field |
  | -------------------- | ------------------------- |
  | `utm_source`         | Campaign Source           |
  | `utm_medium`         | Campaign Medium           |
  | `utm_campaign`       | Campaign Name             |
  | `utm_term`           | Campaign Term             |
  | `utm_content`        | Campaign Content          |
  | `gclid`              | Google Click ID           |

- **Condition** (optional) — only send to Tripleseat when a condition is met.

Save the feed.

## 6. Test it

1. Visit any page with tracking params, e.g.
   `https://yoursite.com/?utm_source=test&utm_medium=cpc&utm_campaign=demo&gclid=abc123`,
   then navigate to the form page (this proves the cookie persists across pages).
2. Submit the form.
3. Check **Forms → Entries** — the hidden UTM fields should hold the values.
4. Check **Forms → System Status → Logging** (enable logging for "Gravity Forms
   Tripleseat Add-On" first) — you'll see the outbound `lead[...]` payload, including
   `lead[location_id]`, `lead[campaign_*]` and `lead[gclid]`, plus Tripleseat's
   response.
5. Confirm the lead (with the right location and campaign data) appears on the
   Tripleseat **Lead Details** page and in reporting.

> Note: submissions hit the **live** Tripleseat Lead API — there is no sandbox. While
> testing, either use a throwaway/test Lead Form ID or expect real test leads in your
> Tripleseat account.

## Restricting the event date to the future (optional)

To stop guests choosing a date in the past, add the CSS class **`gf-future-date`** to
your Date field:

1. Edit the Date field → **Appearance** tab → **Custom CSS Class** → add `gf-future-date`.

That's it. The datepicker then won't offer past dates, and a past date is rejected on
submission with "Please select a date that is today or in the future." The class works
on any form or Date field — there's nothing else to configure.

## Field reference

**Lead fields:** `first_name`, `last_name`, `email_address` (required),
`phone_number`, `company`, `event_description`, `event_date`, `start_time`,
`end_time`, `guest_count`, `location_id`, `additional_information`.

**Campaign fields:** `campaign_source`, `campaign_medium`, `campaign_name`,
`campaign_term`, `campaign_content`, `gclid`.
