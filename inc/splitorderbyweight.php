<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
  Slipt according to select attribute
 * */
include( sow_sunarc_plugin_dir . '/inc/functions.php');

if (!class_exists('sow_split_weight_class')) {

    class sow_split_weight_class
    {
        /**
         * @var string
         */
        public $name = "Sunarc Split Order by Weight";

        /**
         * @var string
         */
        public $description = "Split orders by weight";

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
            add_action('woocommerce_checkout_order_processed', array($this, 'sow_checkout_split_order_by_weight_processed'), 20, 1);

            remove_filter('woocommerce_thankyou_order_received_text', 'filter_woocommerce_thankyou_order_received_text', 10);
            add_filter('woocommerce_thankyou_order_received_text', array($this, 'sow_change_order_received_text'), 10, 2);

            add_filter('woocommerce_locate_template', array($this, 'sow_thank_you_page_template'), 10, 3);
            add_filter('woocommerce_my_account_my_orders_query', array($this, 'sow_my_account_my_orders_query'), 20, 1);
        }

        public function sow_checkout_split_order_by_weight_processed($order_id)
        {
            $newOrdersIfSplitted = get_option('sow_splitneworder') == 'yes' ? 1 : 0;
            $orderIds = [];
            if (isset($order_id)) {
                $sow_splitorderbyweight_value = sprintf('%0.2f', round(get_option('sow_splitorderbyweight_value'), 2));
                settype($sow_splitorderbyweight_value, 'float');

                $parent_order = wc_get_order($order_id);
                $paymentMethod = $parent_order->get_payment_method_title();
                $method = $parent_order->get_payment_method();

                // Set Parent Order status as Pending. As processing type orders are non editable.
                $parent_order->update_status('processing');

                //Sort Cart item based on weight
                $products_in_cart = $this->sortCartProducts();

                // ASSIGN SORTED ITEMS TO CART
                $cart_contents = array();
                foreach ($products_in_cart as $cart_key => $cartItem) {
                    $cartKey = $cartItem['cartkey'];
                    $cart_contents[$cartKey] = WC()->cart->cart_contents[$cartKey];
                }
                WC()->cart->cart_contents = $cart_contents; //Set cart items
                $cart = WC()->cart->get_cart();     //Get cart items.
                $cartDiscountCode = (current(WC()->cart->get_applied_coupons()) !== false) ? current(WC()->cart->get_applied_coupons()) : '';

                $multipleItemedOrder = $this->getOrdersByBestFitModal($cart, $sow_splitorderbyweight_value);

                //Based on BEST FIT MODEL, SINGLE ORDER of multiple items to be one order.
                if (count($multipleItemedOrder) > 0) {
                    $totalOrders = count($multipleItemedOrder);
                    // Iterating through order shipping items
                    $shippingInfo = sunarc_sow_getOrderShippingInfo($parent_order, $totalOrders, true, count($multipleItemedOrder));

                    $address = sunarc_sow_prepareOrderAddressArr();
                    $shippingaddress = prepareOrderShippingAddressArr();
                    $user_id = get_current_user_id();
                    $counter = 0;
                    foreach ($multipleItemedOrder as $key => $splittedOrderItems) {
                        $order = wc_create_order();
                        $order->update_status('processing');
                        $note = isset($_POST['order_comments']) ? sanitize_text_field($_POST['order_comments']) : '';
                        $order->set_customer_note($note);

                        $order->set_address($address, 'billing');
                        $order->set_address($shippingaddress, 'shipping');
//                        $order->calculate_totals();
                        $orderId = sunarc_sow_getSavedOrderId($order);
                        sunarc_sow_updateOrderPostMeta($orderId, $user_id, $note, $paymentMethod, $method, $order, false, $parent_order, $newOrdersIfSplitted);
                        $sum = 0;
                        foreach ($splittedOrderItems as $values) {
                            $values = $values['cart_item'];
                            $sum = (float)$sum + (float)$values['line_total'];
                            $_product = $values['data']->post;
                            $product_title = $_product->post_title;
                            $qty = $values['quantity'];

                            $variation_id = $values['variation_id'];
                            $item_id = wc_add_order_item($orderId, array(
                                'order_item_name' => $product_title,
                                'order_item_type' => 'line_item'
                            ));
                            if ($item_id) {
                                // add item meta data
                                sunarc_sow_addItemMetaData($qty, $item_id, $values, $variation_id);
                            }
                        }
                        ## ------------- ADD SHIPPING PROCESS ---------------- ##
                        $new_ship_price = $this->addShippingToOrder($order, $shippingInfo);
                        $sum = (float)$sum + $new_ship_price;
                        update_post_meta($orderId, '_order_total', wc_format_decimal($sum, get_option('woocommerce_price_num_decimals')));
                        $order->update_status('processing');
                        $orderIds[] = $orderId;
                        $order_data = wc_get_order( $orderId );
                        $order_data->calculate_taxes();
                        $order_data->calculate_totals();
                        if($cartDiscountCode != ''){
                            $order_data->add_coupon($cartDiscountCode, $order_data->get_discount_total(), $order_data->get_discount_tax());
                        }
                        $counter++;
                    }
                }
                //Send Order notifications.
                if (!empty($orderIds)) {
                    update_post_meta($order_id, 'order_ids', serialize($orderIds));
                    sunarc_sow_mail_notifications($orderIds);
                }
            }
        }

        function sow_change_order_received_text($str, $order)
        {
            $qs = $_SERVER['REQUEST_URI'];
            $qs = explode('/', $qs);
            $qs = array_filter($qs, 'strlen');
            if(!is_numeric(end($qs))){
                array_pop($qs);
            }

            if(is_numeric(end($qs))){
                $order_id = end($qs);
                $order = wc_get_order($order_id);
            }
            if(empty($order)){
                $checkout_url = wc_get_cart_url();
                wp_redirect( $checkout_url);
                exit;
            } else {
                $order_id = method_exists($order, 'get_id') ? $order->get_id() : '';
                $parent_order = wc_get_order($order_id);
                $parent_order->update_status('wc-splitted-order');
                if (!empty($order_id)) {
                    $str .= sow_sunarc_change_order_received_text($order_id);
                }
            }
            return $str;
        }

        public function sow_my_account_my_orders_query($orders)
        {
            $orders = sunarc_sow_filteredOrders($orders);
            return $orders;
        }

        public function sow_thank_you_page_template($template, $template_name, $template_path)
        {
            if ('checkout/thankyou.php' == $template_name) {
                $template = sow_sunarc_plugin_dir . '/inc/checkout/thankyou.php';
            }
            return $template;
        }

        /**
         * @return array
         */
        protected function sortCartProducts()
        {
            $cartProducts = array();
            foreach (WC()->cart->get_cart_contents() as $key => $item) {
                $itemData = $item['data'];
                $cartProducts[] = array(
                    'cartkey' => $key,
                    'name' => $itemData->get_name(),
                    'quantity' => $item['quantity'],
                    'weight' => (float)((float)$itemData->get_weight() * $item['quantity']),
                    'type' => $itemData->get_type()
                );
            }

            // SORT CART ITEMS
            usort($cartProducts, "sortByWeight");
            return $cartProducts;
        }

        /**
         * @param $order
         * @param array $shippingInfo
         * @return mixed
         */
        protected function addShippingToOrder($order, array $shippingInfo)
        {
            $country_code = $order->get_shipping_country();

            // Set the array for tax calculations
            $calculate_tax_for = array(
                'country' => $country_code,
                'state' => '', // Can be set (optional)
                'postcode' => '', // Can be set (optional)
                'city' => '', // Can be set (optional)
            );

            // Optionally, set a total shipping amount
            $new_ship_price = $shippingInfo['shipping_method_total'];

            // Set a new instance of the WC_Order_Item_Shipping Object
            $shippingItem = sunarc_sow_setOrderShipping($shippingInfo, $new_ship_price, $calculate_tax_for);

            $order->add_item($shippingItem);
            $order->calculate_totals();
            return $new_ship_price;
        }


        /**
         * @param array $cart
         * @param $sow_splitorderbyweight_value
         * @return array
         */
        protected function getOrdersByBestFitModal(array $cart, $sow_splitorderbyweight_value)
        {
            $filteredArr = [];
            $counterFlag = 0;
            $resultArr = [];
            $CONFIG = $sow_splitorderbyweight_value;
            $counter = 0;
            foreach ($cart as $i => $orderItemArray) {
                $productId = $orderItemArray['product_id'];
                $productQuantity = $orderItemArray['quantity'];
                $orderItemArr = $orderItemArray['data'];
                $productName = $orderItemArr->get_name();
                $productWeight = (float)($productQuantity * (float)$orderItemArr->get_weight());
                if (($orderItemArr->get_type() === 'downloadable' || $orderItemArr->get_type() === 'virtual')) {
                    $filteredArr[] = ['weight' => $productWeight, 'id' => $productId, 'name' => $productName, 'cart_item' => $orderItemArray];
                } else {
                    if (count($filteredArr) > 0)
                        $counterFlag = array_sum(array_column($filteredArr, 'weight'));

                    $counterFlag = (float)$counterFlag + (float)$productWeight;
                    if ($counterFlag > $CONFIG) {
                        $arraySum = array_sum(array_column($filteredArr, 'weight'));
                        if (($arraySum + $productWeight) > $CONFIG && count($filteredArr) > 0) {
                            array_push($resultArr, $filteredArr);
                            $filteredArr = [];
                        }

                        $filteredArr[] = ['weight' => $productWeight, 'id' => $productId, 'name' => $productName, 'cart_item' => $orderItemArray];
                    } else {
                        $filteredArr[] = ['weight' => $productWeight, 'id' => $productId, 'name' => $productName, 'cart_item' => $orderItemArray];
                    }
                }
                if($counter == (count($cart) - 1)){
                    array_push($resultArr, $filteredArr);
                }
                $counter++;
            }
            return $resultArr;
        }
    }
}
new sow_split_weight_class();