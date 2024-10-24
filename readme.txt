=== Dokan Invoice ===
Contributors: tareq1988, wedevs, nizamuddinbabu
Donate Link: http://tareq.co/donate/
Tags: WooCommerce, Multi seller, Multi vendor, Dokan, Invoice, PDF
Requires at least: 6.4
Tested up to: 6.6.2
WC requires at least: 8.0.0
WC tested up to: 9.3.3
Stable tag: 1.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

PDF Invoicing system for Admin, Seller and Customer

== Description ==
> #### This is an add-on to use with [Dokan](https://wedevs.com/products/plugins/dokan/?utm_source=wporg&utm_medium=cta&utm_campaign=dokan-lite) and [Dokan lite](https://wordpress.org/plugins/dokan-lite/) plugin.
> And it is also dependent to [WooCommerce PDF Invoices &amp; Packing Slips](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/). See installation instructions for details.

WooCommerce PDF Invoices & Packing Slips is a renowned plugin to use with WooCommerce to generate invoices and packing slips. We have made this add-on to make it compatible with the plugin.

* Admin can generate invoices from `wp-admin → WooCommerce → Orders` for parent and sub-orders
* Customers can download invoices for their completed orders from `my-account` page.
* Sellers can generate invoices for their orders from `dashboard → Orders`.

All the invoices generated while the above mentioned plugin and this add-on, will contain the store name that sold the products to the customer.

== Installation ==

1. Navigate to wp-admin → Plugins and make sure either Dokan Lite or the paid version of Dokan is installed and activated.
2. Click `Add New` and type "WooCommerce PDF Invoices" in the search box and hit enter to search.
3. If the plugin author is *Ewout Fernhout*, then that plugin is the right one; hit install and activate.
4. Now come back to the plugins page and click `Add New` and type "Dokan Invoice" in the search box and hit enter to search.
5. No one else will make an add-on for other than us and you will definitely see the author name *weDevs*, so hit install.
6. Now you're done and no configuration is required.

To know about the usage and the features, please read the documentation for **[How to install and use Dokan Invoice](http://docs.wedevs.com/category/add-ons/dokan-add-ons/dokan-invoice/)**

== Frequently Asked Questions ==

= I do not like the mail template. Do you have any other design? =

No. We are using the default template provided by *Ewout Fernhout*. If you need to customize anything, please contact him.

= I need to edit some information. How can I do that? =

You can use any PDF editing software like- *Adobe Acrobat* to edit the generated copy.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

v1.2.3 -> 23 Oct 2024
------------------------
- **Update:** WordPress v6.6.2 compatibility
- **Update:** PDF Invoices & Packing Slips for WooCommerce v3.9.0 compatibility
- **Update:** Rewrite dokan store name adding implementation
- **Fix:** Dependency error with PDF Invoices for WooCommerce v3.9.0 on plugin activation

v1.2.2 -> 16 Sep 2023
------------------------
- **Update:** WordPress 6.3.1 compatibility
- **Update:** Added HPOS (High Performance Order Storage) support

v1.2.1 -> 22 May 2021
------------------------
- [new] Added new filter hook: dokan_invoice_single_seller_address
- [fix] Made some string translatible
- [update] WordPress latest version compatibility

v1.2.0 -> 21 August 2019
------------------------
- [new] Address for refunds & better 2.0 support (#2)
- [fix] Vendor info in sub order

v1.1.0 -> 9 September 2017
------------------------
- [new] Compatibility with WooCommerce PDF Invoices & packing slips 2.0+
- [fix] showing error on Order action for vendor dashboard

= 1.0.3 =
------------------------
- [new] Compatibility with WooCommerce 3.0+
- [fix] showing warning on Order action under My Account page

= 1.0.2 =
* [fix] Text domain
* [New] Compatibility with older versions
* [New] Vendor Information on the PDF file.

= 1.0.1 =
* [new] Dependency error notice added
* [new] Fully integrated with WooCommerce PDF invoices templates
* [fix] Permission issue fixed for customer and admin
* [fix] Style and template issue fixed
* [fix] Seller email code issue fixed
* [fix] Sellers can download their sales invoice from dashboard order page

= 1.0 =
* Initial Release

== Upgrade Notice ==

= 1.1.0 =
**Important Update** This update is compatible with WooCommerce PDF Invoices & Packing Slips 2.0+ . Make sure to update to the latest version of **WooCommerce PDF Invoices & Packing Slips** before updating.
