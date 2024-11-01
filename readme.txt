=== Split order by weight for WooCommerce ===
Contributors: sunarc
Donate link: https://example.com/
Tags: WooCommerce, Orders, Split, Split Order By Weight
Requires at least: 5.0
Tested up to: 6.2
Stable tag: 1.0.7
WC requires at least: 4.9
WC tested up to: 6.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

“Split order by weight” plugin can split an order automatically into multiple orders based on the weight of the items in the cart. 

== Description ==
* Author: [Suncart Store](https://www.suncartstore.com)

“Split order by weight” Plugin allows splitting an order into separate orders based on weight. 

This Plugin can be used to split an order automatically into multiple orders based on the weight of the products in the cart. The customer will receive different order ids for their ordered cart. 

With different order ids, customers can view all the order ids in their Order History and track each item separately. 

====Split order for WooCommerce Features====
**A) Default Condition**
> - When the condition is Default then the order is split irrespective of any weight. 
> - All items in the cart available will be splitted into a seperate order. 
	Example: If an order has 4 products then the order is split into 4 different orders no matter what the weight of items are.

**B) Split by Weight**
> - When we enable this option, a threshold weight value is set in woocommerce settings. 
> - Split by weight means order splits only if the total weight of products comes under threshold weight. 
> - In case of more than one item in order, the number of split orders depends on the total occurrence of this case.


====A brief Markdown Example====
We can understand the work process of the Plugin with the below-given example.

This Plugin will split order by weight into multiple orders based on the specified weight in the configuration. If the weight is 1lbs and the cart has a below scenario then it will split as below scenario.

> #### Example: Cart Items

> - We have set 2 Kg as threshold weight in the backend and we have 5 products in order out of which only two products have weight 1 kg others have weight less than 1 Kg. Then the order will split in 2 different orders having different order ids. In first order, two items with 1 kg weight each and the rest of items will be in next order.


> P1 = 1 Kg
> P2 = .6 Kg 
> P3 = .3 Kg 
> P4 = .1 Kg
> P5 = 1 Kg
> 
> Then it will be split like:
> 
> Order 1 = P1 + P5,
> 
> Order 2 = P2 + P3 + P4,
> 
> Note: Here is P means = Product.


**Developer Help**

Bug reports and feedbacks are always welcome. [Report here](https://wordpress.org/support/plugin/split-order-by-weight-for-woocommerce).


== Installation ==
= Split Order by weight for Woocommerce =
1. Go to Plugins > Add New Plugin*, search for **Split order by weight for woocommerce** and click "*Install now*"
2. Alternative method, download the plugin and Upload the Split order by weight for woocommerce folder to the /wp-content/plugins/directory
3. Activate the plugin
4. Now Go to Woocommerce settings and find the last tab such Split order by weight.
5. And Choose option to enable or disable the plugin functionalities.
6. Set the configuration according to your requirements.
6. That's all

OR, See detailed doc, [how to install a WordPress plugin](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins).


= Need more help? =
Please [open a support request](https://wordpress.org/support/plugin/split-order-by-weight-for-woocommerce/).

= Missing something? =
If you would like to have an additional feature for this plugin, [let me know](https://www.suncartstore.com/sunarc-support/)


== Frequently Asked Questions ==
= Is this Plugin compatible with wordpress product types? =
Yes, this Plugin supports wordpress product types.
= Does the user make a one-time payment or is it multiple payments for the different orders created? =
There is only one payment taken for all the items in the cart, once the order is placed it split into different IDs.
= Is this Plugin compatible with the Wordpress version 5.5.? =
Yes, our Plugin is compatible with Wordpress version 5.5.
= How are the payments for the order created in the Plugin taken? =
It does not affect any payment process, works similarly to the default configuration.
= Is it compatible with all payment gateways? Like Paypal =
Currently, Our Plugin supports only PayPal payment gateway and offline payment methods. 
= How is the payment handled when we connect with a Payment Service Provider? =
The payment is being taken before the order is split so the complete order amount is taken from the pay.
= Will the customer pay multiple times? =
Payment will be made only once for a complete order amount, later the order splits as per the items in cart.

== License ==
This WordPress plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version. 
This WordPress plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this WordPress plugin. If not, [see](http://www.gnu.org/licenses/gpl-2.0.html).

== Screenshots ==
1. Woocommerce setting tab to configure split order plugin.

== Changelog ==
= 1.0.6 =
* Updates - Compatibility check with latest version of wordpress 6.2
= 1.0.5 =
* Updates - Compatibility check with latest version of wordpress 6.1
= 1.0.4 =
* Updates - Compatibility check with latest version of wordpress 6.0
= 1.0.3 =
* Updates - Compatibility check with latest version of wordpress 5.9
= 1.0.2 =
* Updates - Compatibility check with latest version of wordpress 5.8
= 1.0.1 - 2021-04-02 =
* Update - Compatibility with wordpress 5.7 and woocommerce 5.1
= 1.0 =	
* Major build.
