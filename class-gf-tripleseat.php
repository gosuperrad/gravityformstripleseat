<?php

/**
 * Gravity Forms Tripleseat Feed Add-On.
 *
 * Sends Gravity Forms submissions to the Tripleseat Lead API. Each form can have one or
 * more feeds; every feed maps form fields to Tripleseat lead fields (including the
 * campaign_* / gclid UTM fields) and targets a Tripleseat lead form by ID. The account
 * public key is stored once in the add-on's global settings.
 *
 * @link https://support.tripleseat.com/hc/en-us/articles/205161948-Lead-Form-API-endpoint
 *
 * @package GF_Tripleseat
 */

if (! defined('ABSPATH')) {
    exit;
}

GFForms::include_feed_addon_framework();

class GF_Tripleseat extends GFFeedAddOn
{
    /**
     * Default Tripleseat Lead API endpoint (JSONP). Overridable in settings.
     */
    public const DEFAULT_API_BASE = 'https://api.tripleseat.com/v1/leads/create.js';

    protected $_version                  = GF_TRIPLESEAT_VERSION;
    protected $_min_gravityforms_version = '2.4';
    protected $_slug                     = 'gravityformstripleseat';
    protected $_path                     = 'gravityformstripleseat/gravityformstripleseat.php';
    protected $_full_path                = __FILE__;
    protected $_title                    = 'Gravity Forms Tripleseat Add-On';
    protected $_short_title              = 'Tripleseat';

    private static $_instance = null;

    /**
     * Get an instance of this class.
     *
     * @return GF_Tripleseat
     */
    public static function get_instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    // # FEED PROCESSING -------------------------------------------------------------------------------------------------

    /**
     * Build the Tripleseat lead payload from the mapped fields and POST it to the API.
     *
     * @param array $feed  The feed object being processed.
     * @param array $entry The entry object being processed.
     * @param array $form  The form object being processed.
     *
     * @return array|void The entry, unchanged.
     */
    public function process_feed($feed, $entry, $form)
    {
        $public_key   = trim((string) $this->get_plugin_setting('public_key'));
        $api_base     = $this->get_api_base();
        $lead_form_id = trim((string) rgars($feed, 'meta/lead_form_id'));

        if ($public_key === '' || $lead_form_id === '') {
            $this->add_feed_error(esc_html__('Tripleseat public key or lead form ID is missing; lead not sent.', 'gravityformstripleseat'), $feed, $entry, $form);

            return $entry;
        }

        $body = array_merge(
            $this->map_lead_values($feed, 'leadFields', $form, $entry),
            $this->map_lead_values($feed, 'campaignFields', $form, $entry),
        );

        if (empty($body['lead[email_address]'])) {
            $this->add_feed_error(esc_html__('Email address is empty; Tripleseat lead not sent.', 'gravityformstripleseat'), $feed, $entry, $form);

            return $entry;
        }

        $url = add_query_arg(
            [
                'lead_form_id' => rawurlencode($lead_form_id),
                'public_key'   => rawurlencode($public_key),
            ],
            $api_base,
        );

        $this->log_debug(__METHOD__ . '(): Sending lead to Tripleseat => ' . print_r($body, true));

        $response = wp_safe_remote_post(
            $url,
            [
                'body'    => $body,
                'timeout' => 30,
            ],
        );

        if (is_wp_error($response)) {
            $this->add_feed_error(sprintf(esc_html__('Tripleseat request failed: %s', 'gravityformstripleseat'), $response->get_error_message()), $feed, $entry, $form);
            $this->log_error(__METHOD__ . '(): Request failed => ' . $response->get_error_message());

            return $entry;
        }

        $code      = wp_remote_retrieve_response_code($response);
        $resp_body = wp_remote_retrieve_body($response);
        $this->log_debug(__METHOD__ . "(): Tripleseat responded [{$code}] => " . print_r($resp_body, true));

        if ($code < 200 || $code >= 300) {
            $this->add_feed_error(sprintf(esc_html__('Tripleseat returned HTTP %s.', 'gravityformstripleseat'), $code), $feed, $entry, $form);
        }

        return $entry;
    }

    /**
     * Map a feed's field_map values into Tripleseat lead[...] body keys, skipping empties.
     *
     * @param array  $feed       The feed object.
     * @param string $field_name The field_map setting name (leadFields|campaignFields).
     * @param array  $form       The form object.
     * @param array  $entry      The entry object.
     *
     * @return array
     */
    private function map_lead_values($feed, $field_name, $form, $entry)
    {
        $mapped = $this->get_field_map_fields($feed, $field_name);
        $values = [];

        foreach ($mapped as $key => $field_id) {
            if (rgblank($field_id)) {
                continue;
            }

            $value = $this->get_field_value($form, $entry, $field_id);

            if ($value !== '' && $value !== null) {
                $values["lead[{$key}]"] = $value;
            }
        }

        return $values;
    }

    /**
     * Resolve the API endpoint, enforcing HTTPS so lead PII is never sent in cleartext.
     *
     * Falls back to the default endpoint if the configured value is empty or not HTTPS.
     *
     * @return string
     */
    private function get_api_base()
    {
        $api_base = esc_url_raw(trim((string) $this->get_plugin_setting('api_base')));

        if ($api_base === '' || stripos($api_base, 'https://') !== 0) {
            return self::DEFAULT_API_BASE;
        }

        return $api_base;
    }

    // # ADMIN -----------------------------------------------------------------------------------------------------------

    /**
     * Global add-on settings (Forms → Settings → Tripleseat).
     *
     * @return array
     */
    public function plugin_settings_fields()
    {
        return [
            [
                'title'       => esc_html__('Tripleseat Account', 'gravityformstripleseat'),
                'description' => esc_html__('Enter the public key for your Tripleseat account. You can find it alongside the lead form embed code in Tripleseat under Settings → Lead Forms.', 'gravityformstripleseat'),
                'fields'      => [
                    [
                        'name'       => 'public_key',
                        'label'      => esc_html__('Public Key', 'gravityformstripleseat'),
                        'type'       => 'text',
                        'class'      => 'medium',
                        'input_type' => 'password',
                        'tooltip'    => '<h6>' . esc_html__('Public Key', 'gravityformstripleseat') . '</h6>' . esc_html__('The account-level public key used to authenticate Lead API requests.', 'gravityformstripleseat'),
                    ],
                    [
                        'name'          => 'api_base',
                        'label'         => esc_html__('API Endpoint', 'gravityformstripleseat'),
                        'type'          => 'text',
                        'class'         => 'large',
                        'default_value' => self::DEFAULT_API_BASE,
                        'tooltip'       => '<h6>' . esc_html__('API Endpoint', 'gravityformstripleseat') . '</h6>' . esc_html__('Leave as the default unless Tripleseat instructs otherwise.', 'gravityformstripleseat'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Per-feed settings (Form Settings → Tripleseat).
     *
     * @return array
     */
    public function feed_settings_fields()
    {
        return [
            [
                'title'  => esc_html__('Tripleseat Feed Settings', 'gravityformstripleseat'),
                'fields' => [
                    [
                        'name'    => 'feedName',
                        'label'   => esc_html__('Feed Name', 'gravityformstripleseat'),
                        'type'    => 'text',
                        'class'   => 'medium',
                        'tooltip' => '<h6>' . esc_html__('Feed Name', 'gravityformstripleseat') . '</h6>' . esc_html__('Enter a name to uniquely identify this feed.', 'gravityformstripleseat'),
                    ],
                    [
                        'name'     => 'lead_form_id',
                        'label'    => esc_html__('Lead Form ID', 'gravityformstripleseat'),
                        'type'     => 'text',
                        'class'    => 'small',
                        'required' => true,
                        'tooltip'  => '<h6>' . esc_html__('Lead Form ID', 'gravityformstripleseat') . '</h6>' . esc_html__('The numeric ID of the Tripleseat lead form that should receive these submissions.', 'gravityformstripleseat'),
                    ],
                ],
            ],
            [
                'title'       => esc_html__('Lead Fields', 'gravityformstripleseat'),
                'description' => esc_html__('Map your form fields to Tripleseat lead fields.', 'gravityformstripleseat'),
                'fields'      => [
                    [
                        'name'      => 'leadFields',
                        'type'      => 'field_map',
                        'field_map' => $this->lead_field_map(),
                    ],
                ],
            ],
            [
                'title'       => esc_html__('Campaign / UTM Fields', 'gravityformstripleseat'),
                'description' => esc_html__('Map hidden form fields holding UTM / GCLID values to Tripleseat campaign fields. Use hidden fields with "Allow field to be populated dynamically" set to utm_source, utm_medium, utm_campaign, utm_term, utm_content and gclid.', 'gravityformstripleseat'),
                'fields'      => [
                    [
                        'name'      => 'campaignFields',
                        'type'      => 'field_map',
                        'field_map' => $this->campaign_field_map(),
                    ],
                ],
            ],
            [
                'title'  => esc_html__('Condition', 'gravityformstripleseat'),
                'fields' => [
                    [
                        'name'           => 'condition',
                        'type'           => 'feed_condition',
                        'checkbox_label' => esc_html__('Enable Condition', 'gravityformstripleseat'),
                        'instructions'   => esc_html__('Send to Tripleseat if', 'gravityformstripleseat'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Tripleseat lead field map definition.
     *
     * @return array
     */
    private function lead_field_map()
    {
        return [
            ['name' => 'first_name', 'label' => esc_html__('First Name', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'last_name', 'label' => esc_html__('Last Name', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'email_address', 'label' => esc_html__('Email Address', 'gravityformstripleseat'), 'required' => true, 'field_type' => ['email', 'hidden']],
            ['name' => 'phone_number', 'label' => esc_html__('Phone Number', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'company', 'label' => esc_html__('Company', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'event_description', 'label' => esc_html__('Event Description', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'event_date', 'label' => esc_html__('Event Date', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'start_time', 'label' => esc_html__('Start Time', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'end_time', 'label' => esc_html__('End Time', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'guest_count', 'label' => esc_html__('Guest Count', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'location_id', 'label' => esc_html__('Location ID', 'gravityformstripleseat'), 'required' => false],
            ['name' => 'additional_information', 'label' => esc_html__('Additional Information', 'gravityformstripleseat'), 'required' => false],
        ];
    }

    /**
     * Tripleseat campaign / UTM field map definition.
     *
     * @return array
     */
    private function campaign_field_map()
    {
        $fields = [
            'campaign_source'  => esc_html__('Campaign Source (utm_source)', 'gravityformstripleseat'),
            'campaign_medium'  => esc_html__('Campaign Medium (utm_medium)', 'gravityformstripleseat'),
            'campaign_name'    => esc_html__('Campaign Name (utm_campaign)', 'gravityformstripleseat'),
            'campaign_term'    => esc_html__('Campaign Term (utm_term)', 'gravityformstripleseat'),
            'campaign_content' => esc_html__('Campaign Content (utm_content)', 'gravityformstripleseat'),
            'gclid'            => esc_html__('Google Click ID (gclid)', 'gravityformstripleseat'),
        ];

        $map = [];
        foreach ($fields as $name => $label) {
            $map[] = [
                'name'       => $name,
                'label'      => $label,
                'required'   => false,
                'field_type' => ['hidden', 'text'],
            ];
        }

        return $map;
    }

    /**
     * Columns shown on the feed list page.
     *
     * @return array
     */
    public function feed_list_columns()
    {
        return [
            'feedName'     => esc_html__('Feed Name', 'gravityformstripleseat'),
            'lead_form_id' => esc_html__('Lead Form ID', 'gravityformstripleseat'),
        ];
    }

    /**
     * Block feed creation until the account public key is configured.
     *
     * @return bool
     */
    public function can_create_feed()
    {
        return trim((string) $this->get_plugin_setting('public_key')) !== '';
    }

    /**
     * Allow feeds to be duplicated from the feed list.
     *
     * @param int|array $id The feed ID, or the feed object when duplicating a form.
     *
     * @return bool
     */
    public function can_duplicate_feed($id)
    {
        return true;
    }

    /**
     * Guidance shown on the feed list when the add-on isn't configured.
     *
     * @return string
     */
    public function feed_list_message()
    {
        if ($this->can_create_feed()) {
            return parent::feed_list_message();
        }

        $settings_label = sprintf(esc_html__('%s Settings', 'gravityformstripleseat'), $this->get_short_title());
        $settings_link  = sprintf('<a href="%s">%s</a>', esc_url($this->get_plugin_settings_url()), $settings_label);

        return sprintf(esc_html__('To get started, configure your Tripleseat public key on the %s page.', 'gravityformstripleseat'), $settings_link);
    }

    /**
     * Plugin/form settings menu icon.
     *
     * @return string
     */
    public function get_menu_icon()
    {
        return file_get_contents($this->get_base_path() . '/assets/images/tripleseat.svg');
    }
}
