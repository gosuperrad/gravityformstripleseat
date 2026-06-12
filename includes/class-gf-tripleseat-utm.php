<?php

/**
 * UTM / GCLID capture for the Gravity Forms Tripleseat Add-On.
 *
 * Bridges marketing query parameters into Gravity Forms hidden fields so they can be
 * mapped to Tripleseat's campaign_* / gclid lead fields. Works even when the form is
 * several clicks from the landing page by persisting the values in a first-party
 * cookie, then feeding them back into Gravity Forms dynamic population.
 *
 * @package GF_Tripleseat
 */

if (! defined('ABSPATH')) {
    exit;
}

class GF_Tripleseat_UTM
{
    /**
     * Cookie used to persist captured parameters across page views.
     */
    public const COOKIE = 'gf_ts_utm';

    /**
     * Query parameters captured and exposed for dynamic population.
     *
     * @var string[]
     */
    private $params = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
    ];

    private static $_instance = null;

    public static function get_instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Register the front-end script and the dynamic-population filters.
     */
    public function init()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_script']);

        foreach ($this->params as $param) {
            add_filter("gform_field_value_{$param}", [$this, 'dynamic_population_value']);
        }
    }

    /**
     * The parameters this add-on captures (e.g. for documentation/tests).
     *
     * @return string[]
     */
    public function get_params()
    {
        return $this->params;
    }

    /**
     * Enqueue the lightweight capture script on the front end.
     */
    public function enqueue_script()
    {
        $src = GF_TRIPLESEAT_URL . 'assets/js/utm-capture.js';

        wp_enqueue_script(
            'gf-tripleseat-utm',
            $src,
            [],
            GF_TRIPLESEAT_VERSION,
            true,
        );

        wp_localize_script('gf-tripleseat-utm', 'gfTripleseatUTM', [
            'cookie' => self::COOKIE,
            'params' => $this->params,
            'days'   => 30,
        ]);
    }

    /**
     * Fall back to the persisted cookie value during Gravity Forms dynamic population.
     *
     * Gravity Forms already prefills from the query string on the current page; this
     * filter covers the case where the visitor landed elsewhere and the value only
     * survives in the cookie.
     *
     * @param string $value The value Gravity Forms resolved (usually empty here).
     *
     * @return string
     */
    public function dynamic_population_value($value)
    {
        if (! empty($value)) {
            return $value;
        }

        $current_filter = current_filter(); // gform_field_value_<param>
        $param          = substr($current_filter, strlen('gform_field_value_'));

        return $this->get_cookie_value($param);
    }

    /**
     * Read a single captured parameter from the cookie.
     *
     * @param string $param Parameter name (e.g. utm_source).
     *
     * @return string
     */
    private function get_cookie_value($param)
    {
        if (empty($_COOKIE[self::COOKIE])) {
            return '';
        }

        $data = json_decode(wp_unslash($_COOKIE[self::COOKIE]), true);

        if (! is_array($data) || empty($data[$param])) {
            return '';
        }

        return sanitize_text_field($data[$param]);
    }
}
