<?php

/**
 * Future-only date restriction for Gravity Forms date fields.
 *
 * Opt-in per field: add the CSS class `gf-future-date` to a Date field's
 * "Custom CSS Class" setting. The datepicker then disallows past dates (client side)
 * and submissions with a past date are rejected (server side). Generic — works on any
 * form/field, with no hardcoded IDs, CDN assets, or polling.
 *
 * @package GF_Tripleseat
 */

if (! defined('ABSPATH')) {
    exit;
}

class GF_Tripleseat_Date
{
    /**
     * Custom CSS class that opts a date field into the future-only restriction.
     */
    public const CSS_CLASS = 'gf-future-date';

    private static $_instance = null;

    public static function get_instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Register the datepicker script and the server-side validation.
     */
    public function init()
    {
        add_action('gform_enqueue_scripts', [$this, 'enqueue_script'], 10, 2);
        add_filter('gform_field_validation', [$this, 'validate_future_date'], 10, 4);
    }

    /**
     * Enqueue the datepicker filter script alongside Gravity Forms' own scripts.
     *
     * @param array $form    The current form.
     * @param bool  $is_ajax Whether the form is being submitted via AJAX.
     */
    public function enqueue_script($form, $is_ajax)
    {
        wp_enqueue_script(
            'gf-tripleseat-future-date',
            GF_TRIPLESEAT_URL . 'assets/js/future-date.js',
            ['gform_gravityforms'],
            GF_TRIPLESEAT_VERSION,
            true,
        );

        wp_localize_script('gf-tripleseat-future-date', 'gfTripleseatDate', [
            'cssClass' => self::CSS_CLASS,
        ]);
    }

    /**
     * Reject submissions whose opted-in date field holds a past date.
     *
     * @param array    $result The validation result (is_valid, message).
     * @param mixed    $value  The submitted field value.
     * @param array    $form   The current form.
     * @param GF_Field $field  The field being validated.
     *
     * @return array
     */
    public function validate_future_date($result, $value, $form, $field)
    {
        if (empty($result['is_valid'])) {
            return $result;
        }

        if ($field->get_input_type() !== 'date' || ! $this->field_has_class($field)) {
            return $result;
        }

        // Single-input datepicker fields submit a string; skip multi-input date fields.
        if (is_array($value) || rgblank($value)) {
            return $result;
        }

        $ymd = GFFormsModel::prepare_date($field->dateFormat, $value);

        if ($ymd !== '' && strtotime($ymd) < strtotime('today')) {
            $result['is_valid'] = false;
            $result['message']  = esc_html__('Please select a date that is today or in the future.', 'gravityformstripleseat');
        }

        return $result;
    }

    /**
     * Whether a field opts into the future-only restriction via its CSS class.
     *
     * @param GF_Field $field The field to check.
     *
     * @return bool
     */
    private function field_has_class($field)
    {
        $classes = preg_split('/\s+/', trim((string) $field->cssClass));

        return in_array(self::CSS_CLASS, $classes, true);
    }
}
