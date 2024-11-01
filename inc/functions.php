<?php
function sunarc_sow_mail_notifications(array $orderIds)
{
    foreach ($orderIds as $new_oder) {
        $wc_mailer = WC()->mailer();
        $mails = $wc_mailer->get_emails();

        //Notification for New Order to Management.
        $new_order_email_template = $mails['WC_Email_New_Order'];
        $new_order_email_template->trigger($new_oder);

        //Notification for Order Processed to Customer.
        $new_order_email_template = $mails['WC_Email_Customer_Processing_Order'];
        $new_order_email_template->trigger($new_oder);
        update_post_meta($new_oder, 'order_email_sent', 'yes');
    }
}

function sunarc_sow_prepareOrderAddressArr()
{
    $address = array(
        'first_name' => sanitize_text_field($_POST['billing_first_name']),
        'last_name' => sanitize_text_field($_POST['billing_last_name']),
        'company' => sanitize_text_field($_POST['billing_company']),
        'email' => sanitize_email($_POST['billing_email']),
        'phone' => sanitize_text_field($_POST['billing_phone']),
        'address_1' => sanitize_text_field($_POST['billing_address_1']),
        'address_2' => sanitize_text_field($_POST['billing_address_2']),
        'city' => sanitize_text_field($_POST['billing_city']),
        'state' => sanitize_text_field($_POST['billing_state']),
        'postcode' => sanitize_text_field($_POST['billing_postcode']),
        'country' => sanitize_text_field($_POST['billing_country'])
    );
    return $address;
}

function sunarc_sow_prepareOrderShippingAddressArr()
{
    $shippingaddress = array(
        'first_name' => sanitize_text_field($_POST['shipping_first_name']),
        'last_name' => sanitize_text_field($_POST['shipping_last_name']),
        'company' => sanitize_text_field($_POST['shipping_company']),
        'email' => isset($_POST['shipping_email']) ? sanitize_email($_POST['shipping_email']) : '',
        'phone' => isset($_POST['shipping_phone']) ? sanitize_text_field($_POST['shipping_phone']) : '',
        'address_1' => sanitize_text_field($_POST['shipping_address_1']),
        'address_2' => sanitize_text_field($_POST['shipping_address_2']),
        'city' => sanitize_text_field($_POST['shipping_city']),
        'state' => sanitize_text_field($_POST['shipping_state']),
        'postcode' => sanitize_text_field($_POST['shipping_postcode']),
        'country' => sanitize_text_field($_POST['shipping_country'])
    );
    return $shippingaddress;
}

function sunarc_sow_setOrderShipping($shippingInfo, $new_ship_price, array $calculate_tax_for)
{
    $item = new WC_Order_Item_Shipping();
    $shipping_method_id = $shippingInfo['shipping_method_id'];
    $shipping_method_title = $shippingInfo['shipping_method_title'];
    $shipping_method_instance_id = $shippingInfo['shipping_method_instance_id'];
    $shipping_method_id = $shipping_method_id . ':' . $shipping_method_instance_id;
    $item->set_method_title($shipping_method_title);
    $item->set_method_id($shipping_method_id); // set an existing Shipping method rate ID
    $item->set_total($new_ship_price); // (optional)
    $item->calculate_taxes($calculate_tax_for);
    return $item;
}

/*function setOrderTax($shippingInfo, $new_ship_price, array $calculate_tax_for)
{
    $item = new WC_Order_Item_Shipping();
    $shipping_method_id = $shippingInfo['shipping_method_id'];
    $shipping_method_title = $shippingInfo['shipping_method_title'];
    $shipping_method_instance_id = $shippingInfo['shipping_method_instance_id'];
    $shipping_method_id = $shipping_method_id . ':' . $shipping_method_instance_id;
    $item->set_method_title($shipping_method_title);
    $item->set_method_id($shipping_method_id); // set an existing Shipping method rate ID
    $item->set_total($new_ship_price); // (optional)
    $item->calculate_taxes($calculate_tax_for);
    return $item;
}*/

function sunarc_sow_getOrderShippingInfo($parent_order, $totalNoOfOrders, $splitOrderByWeight = false, $splittedOrderItems = 0)
{
    $shippingInfo = array();
    foreach ($parent_order->get_items('shipping') as $item_id => $shipping_item_obj) {
        $shippingInfo['shipping_method_title'] = $shipping_item_obj->get_method_title();
        $shippingInfo['shipping_method_id'] = $shipping_item_obj->get_method_id(); // The method ID
        $shippingInfo['shipping_method_instance_id'] = $shipping_item_obj->get_instance_id(); // The instance ID
        $shipping_method_total = $shipping_item_obj->get_total();
        if ($splitOrderByWeight === true) {
            $shippingDivide = 0;
            if ($splittedOrderItems > 0) {
                $shippingDivide += $splittedOrderItems;
            }
            if ($shippingDivide > 0) $shipping_method_total = (float)$shipping_method_total / $shippingDivide;
        } else if ($totalNoOfOrders > 0) {
            $shipping_method_total = (float)$shipping_method_total / $totalNoOfOrders;
        }
        $shippingInfo['shipping_method_total'] = (float)$shipping_method_total;
        $shippingInfo['shipping_method_total_tax'] = (float)$shipping_item_obj->get_total_tax() / $totalNoOfOrders;
        $shippingInfo['shipping_method_taxes'] = $shipping_item_obj->get_taxes();
    }
    return $shippingInfo;
}

function sunarc_sow_getSavedOrderId($order)
{
    return $order->id;
//    return method_exists($order, 'get_id') ? $order->get_id() : $order->id;
}

/**
 * @param $order
 * @param array $shippingInfo
 * @return mixed
 */
/*function addShippingToOrder($order, array $shippingInfo)
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
    $shippingItem = setOrderShipping($shippingInfo, $new_ship_price, $calculate_tax_for);

    $order->add_item($shippingItem);
    $order->calculate_totals();
    return $new_ship_price;
}*/

function sunarc_sow_updateOrderPostMeta($orderId, $user_id, $note, $paymentMethod, $method, $order, $isDefault, $parentOrderData = array(), $newOrdersIfSplitted = 0)
{
    update_post_meta($orderId, '_customer_user', $user_id);
    $orderPost = array(
        'ID' => $orderId,
        'post_excerpt' => $note,
    );

    wp_update_post($orderPost);
    if ($isDefault == true) {
        update_post_meta($orderId, '_order_spliterbyweight', 'default');
    } else {
        update_post_meta($orderId, '_order_spliterbyweight', 'specific');
    }
    $parentOrderId = method_exists($parentOrderData, 'get_id') ? $parentOrderData->get_id() : $parentOrderData->id;
    update_post_meta( $orderId, '_order_discount', get_post_meta($parentOrderId, '_order_discount', true) );
    update_post_meta( $orderId, '_cart_discount', get_post_meta($parentOrderId, '_cart_discount', true) );

    update_post_meta($orderId, '_payment_method_title', $paymentMethod);
    update_post_meta($orderId, '_payment_method', $method);
    update_post_meta($orderId, '_transaction_id', $parentOrderData->get_transaction_id());
    update_post_meta($orderId, '_billing_address_index', implode(' ', $order->get_address('billing')));
    update_post_meta($orderId, '_shipping_address_index', implode(' ', $order->get_address('shipping')));
//    update_post_meta($orderId, '_order_discount', $parentOrderData['discountTotal']);
//    update_post_meta($orderId, '_cart_discount', $parentOrderData['cartDiscountTotal']);
    update_post_meta($orderId, '_order_tax', wc_format_decimal(WC()->cart->tax_total));
    update_post_meta($orderId, '_order_shipping_tax', wc_format_decimal(WC()->cart->shipping_tax_total));
    update_post_meta($orderId, '_order_key', 'wc_' . apply_filters('woocommerce_generate_order_key', uniqid('order_')));
    update_post_meta($orderId, '_order_currency', get_woocommerce_currency());
    update_post_meta($orderId, '_prices_include_tax', get_option('woocommerce_prices_include_tax'));
    update_post_meta($orderId, '_customer_ip_address', isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    update_post_meta($orderId, '_customer_user_agent', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
}

function sunarc_sow_addItemMetaData($qty, $item_id, $cart_item, $variation_id)
{
    $tax_class = isset($cart_item['tax_class']) ? $cart_item['tax_class'] : '';
    $product_id = $cart_item['product_id'];
    $line_subtotal = $cart_item['line_subtotal'];
    $line_subtotal_tax = $cart_item['line_subtotal_tax'];
    $line_total = $cart_item['line_total'];
    $line_tax = $cart_item['line_tax'];
    $line_tax_data = $cart_item['line_tax_data'];

    wc_add_order_item_meta($item_id, '_qty', $qty);
    wc_add_order_item_meta($item_id, '_tax_class', $tax_class);
    wc_add_order_item_meta($item_id, '_product_id', $product_id);
    wc_add_order_item_meta($item_id, '_variation_id', $variation_id);
    wc_add_order_item_meta($item_id, '_line_subtotal', $line_subtotal);
    wc_add_order_item_meta($item_id, '_line_subtotal_tax', $line_subtotal_tax);
    wc_add_order_item_meta($item_id, '_line_total', $line_total);
    wc_add_order_item_meta($item_id, '_line_tax', $line_tax);
    wc_add_order_item_meta($item_id, '_line_tax_data', $line_tax_data);
    if (isset($cart_item['variation_data']) && is_array($cart_item['variation_data'])) {
        foreach ($cart_item['variation_data'] as $key => $value) {
            wc_add_order_item_meta($item_id, str_replace('attribute_', '', $key), $value);
        }
    }
}

function sunarc_sow_currency_symbol()
{
    return (function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$');
}

function sunarc_sow_filteredOrders($orders)
{
    $orders = array(
        'limit' => -1,
        'offset' => null,
        'page' => 1,
        'meta_key' => '_order_spliterbyweight',
        'orderby' => 'id', //meta_value_num
        'order' => 'DESC', //ASC
        'customer' => get_current_user_id(),
        'paginate' => true,
        'meta_query' => array(
            array(
                'key' => '_order_ispliter', //meta type is plain string and i need results alphabetically.
                'value' => array ( 'default', 'specific' ),
                'compare' => 'IN'
            ),
        ),
    );
    return $orders;
}

function sow_sunarc_change_order_received_text($order_id)
{
    $woocommerceCurrency = get_woocommerce_currency_symbol(get_option('woocommerce_currency'));

    ob_start();
    $parent_order = wc_get_order($order_id);
    $paymentMethod = $parent_order->get_payment_method_title();
    $cart_total = 0;
    $posts_array = unserialize(get_post_meta($order_id, 'order_ids', true));

    if (!empty($posts_array)) {
        $order_total = 0;
        foreach ($posts_array as $post_data) {
            $this_order = wc_get_order($post_data);
            $total_amount = $this_order->get_total();
            $order_total += $total_amount;
            $this_order->calculate_totals();
        }
        ?>
        <section class="woocommerce-order-details">
            <h2 class="woocommerce-order-details-title"><?php _e('Split') . ' ' . count($posts_array) > 1 ? _e('Orders') : _e('Order'); ?></h2>
            <?php
            $cart_total += $order_total;
            foreach ($posts_array as $post_data) {
                $child_order = wc_get_order($post_data);
                $child_order_data = $child_order->get_data();
                ?>
                <h3 class="child_order_heading"><?php _e('Order Number'); ?><?php echo ': ' . $child_order_data['id']; ?></h3>
                <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                    <thead>
                    <tr>
                        <th class="woocommerce-table-product-name product-name"><?php _e('Product'); ?></th>
                        <th class="woocommerce-table-product-table product-total"><?php _e('Total'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($child_order->get_items() as $order_items) {
                        $taxAmt = number_format($child_order_data['total_tax'], 2);
                    ?>
                        <tr class="woocommerce-table-line-item order_item">
                            <td class="woocommerce-table-product-name product-name">
                                <a href="<?php echo get_permalink($order_items->get_product_id()); ?>">
                                    <?php echo $order_items['name']; ?>
                                </a>
                                <strong class="product-quantity">x <?php echo $order_items->get_quantity(); ?></strong>
                            </td>
                            <td class="woocommerce-table-product-total product-total">
                                        <span class="woocommerce-Price-amount amount">
                                            <span class="woocommerce-Price-currencySymbol"><?php echo $woocommerceCurrency; ?></span>
                                            <?php echo number_format($order_items->get_subtotal(), 2); ?>
                                        </span>
                            </td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td><?php _e('Discount'); ?>:</td>
                        <td>
                            <span class="woocommerce-Price-currencySymbol">
                                <?php
                                echo (number_format($child_order_data['discount_total'], 2)) > 0 ? '-' : '';
                                echo sunarc_sow_currency_symbol(); ?>
                            </span>
                            <?php
                            echo (number_format($child_order_data['discount_total'], 2)) > 0 ? number_format($child_order_data['discount_total'], 2) : 0; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Shipping'); ?>:</td>
                        <td>
                            <span class="woocommerce-Price-currencySymbol"><?php echo sunarc_sow_currency_symbol(); ?> </span>
                            <?php echo number_format($child_order_data['shipping_total'], 2); ?>
                        </td>
                    </tr>
                    <?php if($taxAmt > 0) { ?>
                    <tr>
                        <td><?php _e('Tax'); ?>:</td>
                        <td>
                            <span class="woocommerce-Price-currencySymbol"><?php echo sunarc_sow_currency_symbol(); ?> </span>
                            <?php echo $taxAmt; ?>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td><?php _e('Payment method'); ?>:</td>
                        <td><?php echo $paymentMethod; ?></td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th scope="row"><?php _e('Total'); ?>:</th>
                        <td>
                                    <span class="woocommerce-Price-amount amount"><span
                                                class="woocommerce-Price-currencySymbol">
                                            <?php echo $woocommerceCurrency; ?>
                                        </span>
                                        <?php echo number_format($child_order->get_total(), 2); ?>
                                    </span>
                        </td>
                    </tr>
                    </tfoot>
                </table>
                <?php
            }
            ?>

            <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                <thead>
                <tr>
                    <th class="woocommerce-table-product-name product-name"><?php _e('Order Total'); ?></th>
                    <th class="woocommerce-table-product-table product-total"><?php _e('Amount'); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr class="woocommerce-table-line-item order_item">
                    <td class="woocommerce-table-product-name product-name">
                        <?php _e('Total amount charged to billing method'); ?>
                    </td>
                    <td class="woocommerce-table-product-total product-total">
                                <span class="woocommerce-Price-amount amount">
                                    <span class="woocommerce-Price-currencySymbol">
                                        <?php echo $woocommerceCurrency; ?>
                                    </span>
                                    <?php echo number_format($cart_total, 2); ?>
                                </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </section>
        <?php
    }
    $str = ob_get_contents();
    ob_end_clean();
    return $str;
}

function sortByWeight($a, $b)
{
    $a = (float)$a['weight'];
    $b = (float)$b['weight'];

    if ($a === $b) return 0;
    return ($a < $b) ? -1 : 1;
}

function getArrayItem($splitAttribute, $parent_order_id, $itemId, $qty, $weight)
{
    return array(
        'order_id' => $parent_order_id,
        'order_item_id' => $itemId,
        'split_attribute' => $splitAttribute,
        'product_id' => $itemId,
        'qty' => $qty,
        'weight' => $weight,
        'total_weight' => ($qty * $weight),
    );
}

/*function splitOrderByWeightFunc($cart, $configWeight, $parent_order_id)
{
    $i = 0;
    $totalWeight = 0;
    $no_need_sum = 0;
    $tmparr = array();
    $result = array();
    $splitarr = array();
    foreach ($cart as $item => $cart_item) {
        $id = $cart_item['product_id'];
        $product = wc_get_product($id);
        $weight = $product->get_weight();
//        $product_type = $product->get_type();
        $order_quantity = $cart_item['quantity'];
        $actualWeight = $order_quantity * $weight;
        $tmparr[$id] = $actualWeight;
        // for getting the configurable product information
//        if ($product_type == 'variable') {
//            echo "<pre>"; print_r($cart_item['variation_data']);
//            echo "<pre>"; print_r($cart_item); exit;
//
//            $childItems = $item->getChildrenItems();
//            foreach ($childItems as $childitem) {
//                $childitem_id = $childitem->getId();
//                $childitem_sku = $childitem->getSku();
//                $config_product = $productRepository->get($childitem_sku);
//                $weight = $config_product->getData('weight');
//                $product_type = $childitem->getProduct()->getTypeId();
//                $order_quantity = $childitem->getQtyOrdered();
//                $order_id = $childitem->getOrderId();
//                $order_item_id = $childitem->getId();
//                $actualWeight = $order_quantity * $weight;
//                $tmparr[$childitem_id] = $actualWeight;
//            }
//        }
        arsort($tmparr);
    }
    foreach ($tmparr as $id => $weight) {
        if ($weight == $configWeight) {
            $splitarr['split'][] = $id;
            unset($tmparr[$id]);
        } else {
            if ($no_need_sum == 0) {
                $totalWeight = array_sum($tmparr);
            }
            if ($totalWeight > $configWeight) {
                $splitarr['split'][] = $id;
                unset($tmparr[$id]);
            } elseif ($totalWeight == $configWeight) {
                $splitarr['splitequal'][] = $id;
                unset($tmparr[$id]);
                $no_need_sum = 1;
            } else {
                $splitarr['remaining'][] = $id;
                unset($tmparr[$id]);
                $no_need_sum = 1;
            }
        }
    }
    foreach ($cart as $item => $cart_item) {
        $itemId = $cart_item['product_id'];
        $product = wc_get_product($itemId);
        $weight = $product->get_weight();
        $qty = $cart_item['quantity'];
        // for configurable product
//        if ($product->get_type() == 'variable') {
//            $childItems = $item->getChildrenItems();
//            foreach ($childItems as $childitem) {
//                $childItemId = $childitem->getId();
//                if (array_key_exists('split', $splitarr) && in_array($childItemId, $splitarr['split'])) {
//                    $result[] = getArrayItem($childitem, $i);
//                    $i++;
//                } elseif (array_key_exists('splitequal', $splitarr) && in_array($childItemId, $splitarr['splitequal'])) {
//                    $result[] = getArrayItem($childitem, "splitequal");
//                } else {
//                    $result[] = getArrayItem($childitem, 'remaining');
//                }
//            }
//        }
        if (($product->get_type() === 'downloadable' || $product->get_type() === 'virtual')) {
            // for Downloadable and virtual product
            $result[] = getArrayItem('noweight', $parent_order_id, $itemId, $qty, $weight);
        }
        // for simple,grouped and bundled product
        if ($product->get_type() === 'simple' || $product->get_type() === 'grouped') {
            if (array_key_exists('split', $splitarr) && in_array($itemId, $splitarr['split'])) {
                $result[] = getArrayItem($i, $parent_order_id, $itemId, $qty, $weight);
                $i++;
            } elseif (array_key_exists('splitequal', $splitarr) && in_array($itemId, $splitarr['splitequal'])) {
                $result[] = getArrayItem("splitequal", $parent_order_id, $itemId, $qty, $weight);
            } else {
                $result[] = getArrayItem('remaining', $parent_order_id, $itemId, $qty, $weight);
            }
        }
    }
    return $result;
}*/

/**
 * @return array
 */
function sunarc_sow_sortCartProducts()
{
    $products_in_cart = array();
    foreach (WC()->cart->get_cart_contents() as $key => $item) {
        $itemData = $item['data'];
        $products_in_cart[] = array(
            'cartkey' => $key,
            'name' => $itemData->get_name(),
            'quantity' => $item['quantity'],
            'weight' => (float)((float)$itemData->get_weight() * $item['quantity']),
            'type' => $itemData->get_type()
        );
    }

    // SORT CART ITEMS
    usort($products_in_cart, "sortByWeight");
    return $products_in_cart;
}

function wc_order_add_discount( $order_id, $title, $amount, $tax_class = '' ) {
    $order    = wc_get_order($order_id);
    $subtotal = $order->get_subtotal();
    $item     = new WC_Order_Item_Fee();

    if ( strpos($amount, '%') !== false ) {
        $percentage = (float) str_replace( array('%', ' '), array('', ''), $amount );
        $percentage = $percentage > 100 ? -100 : -$percentage;
        $discount   = $percentage * $subtotal / 100;
    } else {
        $discount = (float) str_replace( ' ', '', $amount );
        $discount = $discount > $subtotal ? -$subtotal : -$discount;
    }

    $item->set_tax_class( $tax_class );
    $item->set_name( $title );
    $item->set_amount( $discount );
    $item->set_total( $discount );

    if ( '0' !== $item->get_tax_class() && 'taxable' === $item->get_tax_status() && wc_tax_enabled() ) {
        $tax_for   = array(
            'country'   => $order->get_shipping_country(),
            'state'     => $order->get_shipping_state(),
            'postcode'  => $order->get_shipping_postcode(),
            'city'      => $order->get_shipping_city(),
            'tax_class' => $item->get_tax_class(),
        );
        $tax_rates = WC_Tax::find_rates( $tax_for );
        $taxes     = WC_Tax::calc_tax( $item->get_total(), $tax_rates, false );

        if ( method_exists( $item, 'get_subtotal' ) ) {
            $subtotal_taxes = WC_Tax::calc_tax( $item->get_subtotal(), $tax_rates, false );
            $item->set_taxes( array( 'total' => $taxes, 'subtotal' => $subtotal_taxes ) );
            $item->set_total_tax( array_sum($taxes) );
        } else {
            $item->set_taxes( array( 'total' => $taxes ) );
            $item->set_total_tax( array_sum($taxes) );
        }
        $has_taxes = true;
    } else {
        $item->set_taxes( false );
        $has_taxes = false;
    }
    $item->save();

    $order->add_item( $item );
    $order->calculate_totals( $has_taxes );
    $order->save();
}