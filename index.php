<?php if (!defined('ABSPATH')) exit;
/*
	Plugin Name: Split Order By Weight for WooCommerce
	Plugin URI: 
	Description: This plugin split order in multiple orders based on weight.
	Version: 1.0.1
	Author: SunArc
	Author URI: https://sunarctechnologies.com/
	Text Domain: woocommerce-split-order-by-weight
	License: GPL2
	This WordPress plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version. This WordPress plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License	along with this WordPress plugin. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

global $wpdb;
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
} else {
    clearstatcache();
}

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
define('sow_sunarc_plugin_dir', dirname(__FILE__));

/*
 * Register a new order status called Splitted
*/
function register_split_order_status()
{
    register_post_status('wc-splitted-order', array(
        'label' => 'Splitted Order',
        'public' => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list' => true,
        'exclude_from_search' => false,
        'label_count' => _n_noop('Splitted Order <span class="count">(%s)</span>', 'Splitted Order <span class="count">(%s)</span>')
    ));
}
add_action('init', 'register_split_order_status', 0);

function add_split_order_to_order_statuses( $order_statuses ) {
    $new_order_statuses = array();
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-splitted-order'] = 'Splitted Order';
        }
    }
//    $order_statuses['wc-splitted-order'] = _x( 'Splitted Order', 'Order status', 'woocommerce' );
    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_split_order_to_order_statuses');

// Adding custom status to admin order list bulk actions dropdown
function custom_dropdown_bulk_actions_shop_order( $actions ) {
    $new_actions = array();

    // Add new order status before processing
    foreach ($actions as $key => $action) {
        $new_actions[$key] = $action;
        if ('mark_processing' === $key) {
            $actions['mark_sow-splitted-order'] = __( 'Change status to Splitted Order', 'woocommerce' );
        }
    }
    return $actions;
}
add_filter( 'bulk_actions-edit-shop_order', 'custom_dropdown_bulk_actions_shop_order', 20, 1 );

function sow_plugin_activate()
{
    //Set default case when plugin activates.
    $option_name = 'sow_splitorderbyweight';
    $sowSplitCase = get_option($option_name);
    if($sowSplitCase !== 'splitbyweight') {
        $new_value = 'default';
        update_option($option_name, $new_value);
    }

    //Exclude new status in Wordpress Analytics status
    $excluded_statuses = get_option( 'woocommerce_excluded_report_order_statuses' );
    if(in_array('wc-splitted-order', $excluded_statuses) === false) {
        $excluded_statuses = array_merge( array( 'wc-splitted-order' ), $excluded_statuses );
    }
    update_option('woocommerce_excluded_report_order_statuses', $excluded_statuses);
}
register_activation_hook(__FILE__, 'sow_plugin_activate');

function sow_deactivation()
{
    $option_name = 'sow_auto_forced';
    $new_value = 'no';
    update_option($option_name, $new_value);
}
register_deactivation_hook(__FILE__, 'sow_deactivation');

/*
 * Check if Woocommerce is installed.
*/
$sow_all_plugins = get_plugins();
$sow_active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if (array_key_exists('woocommerce/woocommerce.php', $sow_all_plugins) && in_array('woocommerce/woocommerce.php', $sow_active_plugins)) {
    $optionVal = get_option('sow_auto_forced');
    $splitOrderByWeightCond = get_option('sow_splitorderbyweight');
    $splitOrderByWeightCondVal = get_option('sow_splitorderbyweight_value');

    if ($optionVal == 'yes') {
        switch ($splitOrderByWeightCond) {
            case 'default' :
                require_once sow_sunarc_plugin_dir . '/inc/splitorderdefault.php';
                break;
            case 'splitbyweight' :
                require_once sow_sunarc_plugin_dir . '/inc/splitorderbyweight.php';
                break;
        }
    }

    function sow_remove_hooks($email_class)
    {
        remove_action('woocommerce_low_stock_notification', array($email_class, 'low_stock'));
        remove_action('woocommerce_no_stock_notification', array($email_class, 'no_stock'));
        remove_action('woocommerce_product_on_backorder_notification', array($email_class, 'backorder'));

        // New order emails
        remove_action('woocommerce_order_status_pending_to_processing_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_pending_to_completed_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_pending_to_on-hold_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_failed_to_processing_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_failed_to_completed_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_failed_to_on-hold_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));

        // Processing order emails
        remove_action('woocommerce_order_status_pending_to_processing_notification', array($email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger'));
        remove_action('woocommerce_order_status_pending_to_on-hold_notification', array($email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger'));

        // Completed order emails
        remove_action('woocommerce_order_status_completed_notification', array($email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger'));

        // Note emails
        remove_action('woocommerce_new_customer_note_notification', array($email_class->emails['WC_Email_Customer_Note'], 'trigger'));
    }
    add_action('woocommerce_email', 'sow_remove_hooks');

    function sow_before_checkout_create_order($order, $data)
    {
        $order->update_meta_data('_custom_meta_hide', 'yes');
    }
    add_action('woocommerce_checkout_create_order', 'sow_before_checkout_create_order', 20, 2);

    /**
     * Adds 'Order Splitted From' column header to 'Orders' page immediately after 'Total' column.
     */
    function sow_add_order_splitted_from_column_header($columns)
    {
        $new_columns = array();
        foreach ($columns as $column_name => $column_info) {
            $new_columns[$column_name] = $column_info;
            if ('order_status' === $column_name) {
                $new_columns['sow_order_type'] = __('Type', 'sow');
                $new_columns['sow_order_splitted_from'] = __('Splitted From', 'sow');
            }
        }

        return $new_columns;
    }
    add_filter('manage_edit-shop_order_columns', 'sow_add_order_splitted_from_column_header', 20);

    /**
     * Adds 'Order Splitted From' column content to 'Orders' page immediately after 'Total' column.
     */
    function sow_add_order_splitted_from_column_content($column)
    {
        global $post;

        if ('sow_order_splitted_from' === $column) {
            //        $order    = wc_get_order( $post->ID );
            $parent_order = get_post_meta($post->ID, '_order_splitted_from', true);

            // don't check for empty() since cost can be '0'
            $content = '-';
            if (strlen($parent_order) > 0) {
                $content = $parent_order;
            }
            echo (string)$content;
        }
        if ('sow_order_type' === $column) {
            //        $order    = wc_get_order( $post->ID );
            $orderType = get_post_meta($post->ID, '_order_spliterbyweight', true);

            // don't check for empty() since cost can be '0'
            $content = 'Main';
            if ('default' == $orderType || 'specific' == $orderType) {
                $content = 'Splitted';
            }
            echo (string)$content;
        }
    }
    add_action('manage_shop_order_posts_custom_column', 'sow_add_order_splitted_from_column_content');

    /**
     * Adjusts the styles for the new 'Order Splitted From' column.
     */
    function sow_add_order_splitted_from_column_style()
    {
        $css = '.widefat .column-order_date, .widefat .column-order_profit { width: 9%; }';
        wp_add_inline_style('woocommerce_admin_styles', $css);
    }
    add_action('admin_print_styles', 'sow_add_order_splitted_from_column_style');

    /*
     * Hide Refund button for main orders.
    */
    function hide_wc_refund_button()
    {
        global $post;

        if (!current_user_can('orders-manager')) {
            return;
        }
        if (strpos($_SERVER['REQUEST_URI'], 'post.php?post=') === false) {
            return;
        }

        if (empty($post) || $post->post_type != 'shop_order') {
            return;
        }
        ?>
        <script>
            jQuery().ready(function () {
                jQuery('.refund-items').hide();
            });
        </script>
        <?php
    }
    add_action('admin_head', 'hide_wc_refund_button');
}

if (!class_exists('sunarc_sow_main_cls')) {
    class sunarc_sow_main_cls
    {
        const ALREADY_BOOTSTRAPED = 1;
        const DEPENDENCIES_UNSATISFIED = 2;

        public function __construct()
        {
            add_action('init', array($this, 'init_sow'));
        }

        public function init_sow()
        {
            define('sow_sunarc_version', '1.0.1');
            try {
                $sow_all_plugins = get_plugins();
                $sow_active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

                if (array_key_exists('woocommerce/woocommerce.php', $sow_all_plugins) && in_array('woocommerce/woocommerce.php', $sow_active_plugins)) {

                    !defined('sow_sunarc_path') && define('sow_sunarc_path', plugin_dir_path(__FILE__));
                    !defined('sow_sunarc_url') && define('sow_sunarc_url', plugins_url('/', __FILE__));

                    require_once(sow_sunarc_plugin_dir . '/classes/function-class.php');

                    SUNARC_SOW_Function_Class::instance();
                } else {
                    deactivate_plugins( plugin_basename( __FILE__ ) );
                    throw new Exception(__('Split Order by weight requires WooCommerce to be activated. Plugin will be deactivated.', 'woocommerce-split-order-by-weight'), self::DEPENDENCIES_UNSATISFIED);
                }
            } catch (Exception $e) {
                if (in_array($e->getCode(), array(self::ALREADY_BOOTSTRAPED, self::DEPENDENCIES_UNSATISFIED))) {
                    $this->bootstrap_warning_message = $e->getMessage();
                }
            }
        }

        // Uninstall Pluign
        function sow_uninstall()
        {
            $option_name = 'sow_auto_forced';
            $new_value = 'no';
            update_option($option_name, $new_value);
            $option_name = 'sow_splitorderbyweight';
            $new_value = '';
            update_option($option_name, $new_value);
        }
    }
}
new sunarc_sow_main_cls();
