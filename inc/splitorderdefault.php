<?php
if (!defined('ABSPATH')) {
    exit;
}
/*
  Slipt according to exist attribute
 * */
include( sow_sunarc_plugin_dir . '/inc/functions.php');

if (!class_exists('sow_default_class')) {

    class sow_default_class
    {
        /**
         * @var string
         */
        public $name = "Sunarc Split Order by Weight";

        /**
         * @var string
         */
        public $description = "Split orders by weight";

        /**
         * sow_default_class constructor.
         */
        public function __construct()
        {
            $this->default_init();
        }

        public function default_init()
        {
            if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
                $this->default_frontend_variation();
            }
        }

        public function default_frontend_variation()
        {
            add_action('woocommerce_checkout_order_processed', array($this, 'sow_checkout_split_order_by_default_processed'), 20, 1);
            remove_filter('woocommerce_thankyou_order_received_text', 'filter_woocommerce_thankyou_order_received_text', 10);
            add_filter('woocommerce_thankyou_order_received_text', array($this, 'sow_change_order_received_text'), 10, 2);
            add_filter('woocommerce_locate_template', array($this, 'sow_thank_you_page_template'), 10, 3);
            add_filter('woocommerce_my_account_my_orders_query', array($this, 'sow_my_account_my_orders_query'), 20, 1);
        }

        /**
         * @param array $cart
         * @param $sow_splitorderbyweight_value
         * @return array
         */
        protected function getOrdersByBestFitModal(array $cart)
        {
            $filteredArr = [];
            $resultArr = [];
            $singleOrders = [];
            $counter = 0;
            foreach ($cart as $i => $orderItemArray) {
                $orderItemArr = $orderItemArray['data'];
                if (($orderItemArr->get_downloadable() == true || $orderItemArr->get_virtual() == true)) {
                    $singleOrders[] = $orderItemArray;
                } else {
                    $filteredArr[] = $orderItemArray;
                }
                if($counter == (count($cart) - 1)){
                    array_push($resultArr, $filteredArr);
                }
                $counter++;
            }
            return array($singleOrders, $resultArr);
        }

        /**
         * @param $order_id
         * @throws WC_Data_Exception
         */
        public function sow_checkout_split_order_by_default_processed($order_id)
        {
            $newOrdersIfSplitted = 1;
            $user_id = get_current_user_id();
            if (isset($order_id)) {
                $parent_order = wc_get_order($order_id);

                $paymentMethod = $parent_order->get_payment_method_title();
                $method = $parent_order->get_payment_method();

                // Set Parent Order status as Pending. As processing type orders are non editable.
                $parent_order->update_status('processing');

                $cartDiscountCode = (current(WC()->cart->get_applied_coupons()) !== false) ? current(WC()->cart->get_applied_coupons()) : '';

                $products_in_cart = sunarc_sow_sortCartProducts();
                // ASSIGN SORTED ITEMS TO CART
                $cart_contents = array();
                foreach ($products_in_cart as $cart_key => $cartItem) {
                    $cartKey = $cartItem['cartkey'];
                    $cart_contents[$cartKey] = WC()->cart->cart_contents[$cartKey];
                }
                WC()->cart->cart_contents = $cart_contents; //Set cart items
                $cart = WC()->cart->get_cart();     //Get cart items.

                list($singleOrder, $multipleItemedOrder) = $this->getOrdersByBestFitModal($cart);

                $orderIds = array();
                $counter = 0;
                $totalOrders = count(current($multipleItemedOrder));
                // Iterating through order shipping items
                $shippingInfo = sunarc_sow_getOrderShippingInfo($parent_order, $totalOrders);

                if(count($multipleItemedOrder) > 0) {
                    foreach (current($multipleItemedOrder) as $item => $cart_item) {
                        //Get Item Information
                        $product = wc_get_product($cart_item['product_id']);
                        $address = sunarc_sow_prepareOrderAddressArr();
                        $shippingaddress = sunarc_sow_prepareOrderShippingAddressArr();
                        $order = wc_create_order();
                        //Split Order & Create new order
                        list($orderId, $sum) = $this->makeOrder($order, $address, $shippingaddress, $user_id, $paymentMethod, $method, $cart_item, $parent_order, $shippingInfo, $product, $cartDiscountCode);
                        update_post_meta($orderId, '_order_total', wc_format_decimal($sum, get_option('woocommerce_price_num_decimals')));
                        $orderIds[] = $orderId;
                        $counter++;
                    }
                }

                if(count($singleOrder) > 0) {
                    $order = wc_create_order();
                    $order->update_status('processing');
                    $note = isset($_POST['order_comments']) ? sanitize_text_field($_POST['order_comments']) : '';
                    $order->set_customer_note($note);

                    $address = sunarc_sow_prepareOrderAddressArr();
                    $shippingaddress = sunarc_sow_prepareOrderShippingAddressArr();
                    $order->set_address($address, 'billing');
                    $order->set_address($shippingaddress, 'shipping');

                    $orderId = sunarc_sow_getSavedOrderId($order);
                    sunarc_sow_updateOrderPostMeta($orderId, $user_id, $note, $paymentMethod, $method, $order, true, $parent_order);
                    $order->calculate_totals();
                    $sum = 0;
                    foreach ($singleOrder as $cart_item) {
                        $sum = (float)$sum + (float)$cart_item['line_total'];
                        $product = wc_get_product($cart_item['product_id']);
                        $product_title = $product->get_name();
                        $qty = $cart_item['quantity'];
                        $variation_id = $cart_item['variation_id'];
                        $item_id = wc_add_order_item($orderId, array(
                            'order_item_name' => $product_title,
                            'order_item_type' => 'line_item'
                        ));
                        if ($item_id) {
                            // add item meta data
                            sunarc_sow_addItemMetaData($qty, $item_id, $cart_item, $variation_id);
                        }
                        $order_data = wc_get_order( $orderId );
                        $order_data->calculate_taxes();
                        $order_data->calculate_totals();
                        if($cartDiscountCode != '' && $order_data->get_discount_total() > 0) {
                            $order_data->add_coupon($cartDiscountCode, $order_data->get_discount_total(), $order_data->get_discount_tax());
                        }
                    }
                    update_post_meta($orderId, '_order_total', wc_format_decimal($sum, get_option('woocommerce_price_num_decimals')));
                    $orderIds[] = $orderId;
                }

                //Send Order notifications.
                if(!empty($orderIds)){
                    update_post_meta($order_id, 'order_ids', serialize($orderIds));
                    sunarc_sow_mail_notifications($orderIds);
                }
            }
        }

        /**
         * @param array $address
         * @param array $shippingaddress
         * @param $user_id
         * @param $paymentMethod
         * @param $method
         * @param $cart_item
         * @return array
         * @throws WC_Data_Exception
         */
        protected function makeOrder($order, array $address, array $shippingaddress, $user_id, $paymentMethod, $method, $cart_item, $parentOrderData, $shippingInfo, $product, $cartDiscountCode)
        {
            $order->update_status('processing');
            $note = isset($_POST['order_comments']) ? sanitize_text_field($_POST['order_comments']) : '';
            $order->set_customer_note($note);
            $order->set_address($address, 'billing');
            $order->set_address($shippingaddress, 'shipping');
            $orderId = $order->get_id();

            sunarc_sow_updateOrderPostMeta($orderId, $user_id, $note, $paymentMethod, $method, $order, true, $parentOrderData);
            $order->calculate_totals();
            $product_title = $product->get_name();
            $qty = $cart_item['quantity'];
            $variation_id = $cart_item['variation_id'];
            $item_id = wc_add_order_item($orderId, array(
                'order_item_name' => $product_title,
                'order_item_type' => 'line_item'
            ));
            if ($item_id) {
                ## ------------- ADD SHIPPING PROCESS ---------------- ##
                // Get the customer country code
                $country_code = $order->get_shipping_country();

                // Set the array for tax calculations
                $calculate_tax_for = array(
                    'country' => $country_code,
                    'state' => '', // Can be set (optional)
                    'postcode' => '', // Can be set (optional)
                    'city' => '', // Can be set (optional)
                    'tax_class' => '', // Can be set (optional)
                );

                // Optionally, set a total shipping amount
                $new_ship_price = $shippingInfo['shipping_method_total'];

                // Set a new instance of the WC_Order_Item_Shipping Object
                $shippingItem = sunarc_sow_setOrderShipping($shippingInfo, $new_ship_price, $calculate_tax_for);

                $order->add_item($shippingItem);
                $order->calculate_totals();

                // add item meta data
                sunarc_sow_addItemMetaData($qty, $item_id, $cart_item, $variation_id);
            }
            $order_data = wc_get_order( $orderId );
            if($cartDiscountCode != '' && $order_data->get_discount_total() > 0) {
                $order_data->add_coupon($cartDiscountCode, $order_data->get_discount_total(), $order_data->get_discount_tax());
            }
            $order_data->calculate_taxes();
            $order_data->calculate_totals();
            $sum = $order_data->get_total();
            return array($orderId, $sum);
        }

        /**
         * @param $str
         * @param $order
         * @return string
         */
        function sow_change_order_received_text($str, $order)
        {
            $qs = explode('/', $_SERVER['REQUEST_URI']);
            $qs = array_filter($qs, 'strlen');
            if(!is_numeric(end($qs))){
                array_pop($qs);
            }

            if(is_numeric(end($qs))){
                $order_id = end($qs);
                $order = wc_get_order($order_id);
            }
            if(empty($order)){
                $cart_url = wc_get_cart_url();
                wp_redirect( $cart_url);
                exit;
            } else {
                $order_id = $order->get_id();

                //Change main order status to Splitted Order.
                $newOrdersIfSplitted = get_option('sow_splitneworder') == 'yes' ? 1 : 0;
                if($newOrdersIfSplitted == 1) {
                    $parent_order = wc_get_order($order_id);
                    $parent_order->update_status('wc-splitted-order');
                }

                if (!empty($order_id)) {
                    $str .= sow_sunarc_change_order_received_text($order_id);
                }
            }
            return $str;
        }

        public function sow_thank_you_page_template($template, $template_name, $template_path) {
            if ('checkout/thankyou.php' == $template_name) {
                $template = sow_sunarc_plugin_dir . '/inc/checkout/thankyou.php';
            }
            return $template;
        }

        /**
         * @param $orders
         * @return array
         */
        public function sow_my_account_my_orders_query($orders)
        {
            $orders = sunarc_sow_filteredOrders($orders);
            return $orders;
        }
    }
}
new sow_default_class();