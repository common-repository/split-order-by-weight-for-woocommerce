<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SUNARC_SOW_Function_Class')):

    class SUNARC_SOW_Function_Class
    {
        /**
         * @var null
         */
        protected static $_instance = null;
        protected static $_weightVal = null;

        /**
         * SOW_Function_Class constructor.
         */
        public function __construct()
        {
            add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
            add_action('woocommerce_settings_tabs_settings_tab', __CLASS__ . '::settings_tab');
            add_action('woocommerce_update_options_settings_tab', __CLASS__ . '::update_settings');

            add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
            if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
                $this->frontend_css_js();
            }
            $weightVal = get_option('sow_splitorderbyweight_value');
            if (is_null(self::$_weightVal)) {
                self::$_weightVal = isset($weightVal) && abs($weightVal) > 0 ? abs($weightVal) : 0;
            }
        }

        /**
         *  Embed Styles & Scripts for Frontend.
         */
        public function frontend_css_js()
        {
            add_action('admin_enqueue_scripts', array($this, 'sow_frontend_scripts'));
            add_action('wp_head', array($this, 'sow_custom_ajax_url'));
        }

        /**
         *
         */
        public function sow_frontend_scripts()
        {
            wp_enqueue_style('woocommerce_admin_styles');
            wp_enqueue_style('wos-custom-style-css', plugins_url('/assets/css/custom_style.css', dirname(__FILE__)), sow_sunarc_version);
            wp_enqueue_script('wos-frontend-js', sow_sunarc_url . 'assets/js/custom.js', array('jquery', 'wp-color-picker'), sow_sunarc_version, true);
        }

        /**
         * @return SOW_Function_Class|null
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         *
         */
        public function sow_custom_ajax_url()
        {
            $html = '<script type="text/javascript">';
            $html .= 'var ajaxurl = "' . admin_url('admin-ajax.php') . '"';
            $html .= '</script>';
            echo $html;
        }

        /**
         * Add a new settings tab to the WooCommerce settings tabs array.
         *
         * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
         * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
         */
        public static function add_settings_tab($settings_tabs)
        {
            $settings_tabs['settings_tab'] = __('Split order by weight', 'splitorder-settings-tab');
            return $settings_tabs;
        }

        /**
         * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
         *
         * @uses woocommerce_admin_fields()
         * @uses self::get_settings()
         */
        public static function settings_tab()
        {
            woocommerce_admin_fields(self::get_settings());
        }

        /**
         * Get all the settings for this plugin for @return array Array of settings for @see woocommerce_admin_fields() function.
         * @see woocommerce_admin_fields() function.
         *
         */
        public static function get_settings()
        {
            $settings = array(
                'section_title' => array(
                    'name' => __('Split Order by Weight', 'splitorder-settings-tab'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'splitorder_tab_sow_section_title'
                ),
                'config' => array(
                    'title'    => __( 'Enable Split Order', 'splitorder-settings-tab' ),
                    'desc'     => __( 'This controls your split order by weight.', 'splitorder-settings-tab' ),
                    'id'       => 'sow_auto_forced',
                    'class'    => 'wc-enhanced-select',
                    'css'      => 'min-width:300px;',
                    'default'  => 'no',
                    'type'     => 'select',
                    'options'  => array(
                        'yes'  => __( 'Yes', 'splitorder-settings-tab' ),
                        'no' => __( 'No', 'splitorder-settings-tab' ),
                    ),
                    'desc_tip' => __('Select Yes for splitting order based on weight, keep No to disable.', 'splitorder-settings-tab'),
                ),
                'attribute' => array(
                    'title'    => __( 'Splitorderweight Conditions', 'splitorder-settings-tab' ),
                    'desc'     => __( 'This controls your split order by weight based on conditions.', 'splitorder-settings-tab' ),
                    'id'       => 'sow_splitorderbyweight',
                    'class'    => 'wc-enhanced-select',
                    'css'      => 'min-width:300px;',
                    'default'  => 'default',
                    'type'     => 'select',
                    'options'  => array(
                        'default'  => __( 'Default', 'splitorder-settings-tab' ),
                        'splitbyweight' => __( 'Split according to weight', 'splitorder-settings-tab' ),
                    ),
                    'desc_tip' => __('Select Yes for splitting order based on weight, keep No to disable.', 'woocommerce'),
                ),
                'attributeval' => array(
                    'name' => __( 'Enter weight condition (' .get_option('woocommerce_weight_unit'). ')', 'splitorder-settings-tab' ),
                    'type' => 'text',
                    'required' => false,
                    'value'  => 1,
                    'custom_attributes' => array('readonly' => 'readonly'), // Enabling read only
                    'desc' => __( 'Enter a weight upto orders will be splited.', 'splitorder-settings-tab' ),
                    'placeholder' => 200,
                ),
                'splitcase' => array(
                    'name'    => __( 'Split Order with main order', 'splitorder-settings-tab' ),
                    'desc'     => __( 'This controls your split order with a main order.', 'splitorder-settings-tab' ),
                    'required' => false,
                    'value'  => 'Yes',
                    'type'     => 'text',
                    'custom_attributes' => array('readonly' => 'readonly'), // Enabling read only
                    'desc_tip' => __('Select Yes for splitting order based on weight, keep No to disable.', 'splitorder-settings-tab'),
                ),
                'section_end' => array(
                    'type' => 'sectionend',
                    'id' => 'splitorder_tab_sow_section_end'
                )
            );
            return apply_filters('wc_settings_sunarc_split_order_by_weight_settings', $settings);
        }

        /**
         * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
         *
         * @uses woocommerce_update_options()
         * @uses self::get_settings()
         */
        public static function update_settings()
        {
            woocommerce_update_options(self::get_settings());
        }
    }
endif;
?>