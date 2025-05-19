<?php
/**
 * Plugin Name: Dokan - PDF Invoice
 * Plugin URI: https://dokan.co/wordpress/
 * Description: A Dokan plugin Add-on to get PDF invoice.
 * Version: 1.2.3
 * Author: weDevs
 * Author URI: https://dokan.co/
 * License: GPL2
 * Text Domain: dokan-invoice
 * WC requires at least: 8.0.0
 * WC tested up to: 9.3.3
 * Requires Plugins: woocommerce, dokan-lite, woocommerce-pdf-invoices-packing-slips
 */

/**
 * Copyright (c) 2017 weDevs (email: info@wedevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */
// don't call the file directly
if ( ! defined( 'ABSPATH' ) )
    exit;

/**
 * Dokan_Invoice class
 *
 * @class Dokan_Invoice The class that holds the entire Dokan_Invoice plugin
 */
class Dokan_Invoice {

    public static $plugin_url;
    public static $plugin_path;
    public static $plugin_basename;
    protected $dokan_invoice_active = 0;

    private $depends_on       = array();
    private $dependency_error = array();

    /**
     * WC PDF Plugin Class Name.
     *
     * @since 1.2.3
     *
     * @var string
     */
    public $wc_pdf_class = '';

    /**
     * Constructor for the Dokan_Invoice class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        self::$plugin_basename = plugin_basename( __FILE__ );
        self::$plugin_url      = plugin_dir_url( self::$plugin_basename );
        self::$plugin_path     = trailingslashit( dirname( __FILE__ ) );

	    $this->wc_pdf_class = version_compare( get_option( 'wpo_wcpdf_version', false ), '3.9.0', '<' ) ? 'WooCommerce_PDF_Invoices' : 'WPO_WCPDF' ;

	    add_action( 'before_woocommerce_init', [ $this, 'add_hpos_support' ] );
        add_action( 'init', array( $this,'localization_setup_and_is_dependency_available') );
        add_action( 'plugins_loaded', array( $this, 'init_hooks' ) );
    }

	/**
	 * Add High Performance Order Storage Support.
	 *
	 * @since 1.2.2
	 *
	 * @return void
	 */
	public function add_hpos_support() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

    /**
     * Initialize plugin for localization
     * check if dependencies installed or not and add error notice
     *
     * @since 1.0.0
     */
    public function localization_setup_and_is_dependency_available(){
        $res = true;

        load_plugin_textdomain( 'dokan-invoice', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

        $this->depends_on['dokan'] = array(
            'name'   => 'WeDevs_Dokan',
            'notice' => sprintf( esc_html__( '%sDokan PDF Invoice%s requires %sDokan plugin%s to be installed & activated!' , 'dokan-invoice' ), '<b>', '</b>', '<a target="_blank" href="https://dokan.co/wordpress/">', '</a>' ),
        );

        $this->depends_on['woocommerce_pdf_invoices'] = array(
            'name'   => $this->wc_pdf_class,
            'notice' => sprintf( esc_html__( '%sDokan PDF Invoice%s requires %sWooCommerce PDF Invoices & packing slips plugin%s to be installed & activated!' , 'dokan-invoice' ), '<b>', '</b>', '<a target="_blank" href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">', '</a>' ),
        );

        foreach ( $this->depends_on as $class ){
            if ( !class_exists( $class['name'] ) ){
                $this->dependency_error[] = $class['notice'];
                $res = false;
            }
        }

        if ($res == false){
            add_action( 'admin_notices', array ( $this, 'dependency_notice' ) );
        }

        return $res;
    }

    /**
     * print error notice if dependency not active
     *
     * @since 1.0.0
     */
    public function dependency_notice(){
        $errors = '';
        $error = '';
        foreach ( $this->dependency_error as $error ) {
            $errors .= '<p>' . $error . '</p>';
        }
        $message = '<div class="error">' . $errors . '</div>';

        echo $message;

        deactivate_plugins( plugin_basename( __FILE__ ) );
    }

    /**
     * Initializes the Dokan_Invoice() class
     *
     * Checks for an existing Dokan_Invoice() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;
        if ( !$instance ) {
            $instance = new Dokan_Invoice();
        }

        return $instance;
    }

    /**
     * Init hooks
     *
     * @return void
     */
    public function init_hooks() {
        if ( ! class_exists( $this->wc_pdf_class ) ) {
            return ;
        }

        add_filter( 'dokan_my_account_my_sub_orders_actions', array( $this, 'dokan_invoice_listing_actions_my_account' ), 50, 2 );
        add_filter( 'wpo_wcpdf_shop_name', array( $this,'wpo_wcpdf_add_dokan_shop_name'), 10, 2 );
        add_filter( 'wpo_wcpdf_shop_address', array( $this,'wpo_wcpdf_add_dokan_shop_details'), 10, 2 );
        add_filter( 'wpo_wcpdf_check_privs', array( $this,'wpo_wcpdf_dokan_privs'), 50, 2 );
    }

    /**
     * Enqueue admin scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     * @uses wp_enqueue_style
     */
    public function enqueue_scripts() {
        wp_enqueue_style( 'dokan-invoice-styles', plugins_url( 'assets/css/style.css', __FILE__ ), false, date( 'Ymd' ) );
    }

    /**
     * Set Dokan_invoice buttons on My Account page
     *
     * Hooked with WP_invoice filter
     *
     * @param array $actions
     * @param WC_Order $order
     *
     * @return array $actions
     */
    public function dokan_invoice_listing_actions_my_account( $actions, $order ) {
        $frontend = new \WPO\WC\PDF_Invoices\Frontend();

        return $frontend->my_account_pdf_link( $actions, $order );
    }

    /**
     * Filter Shop name according to Store name
     *
     * @since 1.1
     *
     * @global type $wpo_wcpdf
     *
     * @param type $shop_name
     *
     * @return string $shop_name
     */
    public function wpo_wcpdf_add_dokan_shop_name( $shop_name, $document = null ) {
        extract( $this->get_order_id_parent_id( $document ) );

        $shop_name_label = apply_filters( 'dokan_invoice_shop_name_label', esc_html__( 'Vendor', 'dokan-invoice' ), $document );

        // If parent order keep Original Store name else set seller store name
        if ( $parent_id == 0 ) {
            if ( function_exists( 'dokan_get_seller_ids_by' ) ) {
                $seller_list = dokan_get_seller_ids_by( $order_id );
            } else {
                $seller_list = array_unique( array_keys( dokan_get_sellers_by( $order_id ) ) );
            }

            if ( 1 === count( $seller_list ) ) {
                $vendor_id  = $seller_list[0];
                $vendor     = dokan()->vendor->get( $vendor_id );
                $store_name = $vendor->get_shop_name();
                $store_name = $store_name ?: '';

                $shop_name .= sprintf( '<br/><br/>%s: %s',  $shop_name_label,  $store_name );
            }
        } else {
            $vendor_id  = dokan_get_seller_id_by_order( $order_id );
            $vendor     = dokan()->vendor->get( $vendor_id );
            $store_name = $vendor->get_shop_name();
            $store_name = $store_name ?: '';

            $shop_name .= sprintf( '<br/><br/>%s: %s',  $shop_name_label,  $store_name );
        }

        return apply_filters( 'dokan_invoice_store_name', $shop_name, $document );
    }

    /**
     * Filter Shop address
     *
     * @since 1.1
     *
     * @global type $wpo_wcpdf
     *
     * @param type $shop_address
     *
     * @return string $shop_address
     */
    public function wpo_wcpdf_add_dokan_shop_details( $shop_address, $document = null ) {
        // get $order_id & $parent_id
        extract( $this->get_order_id_parent_id( $document ) );

        //If parent order print Store names only after address else Print Seller Store Address
        if ( $parent_id == 0 ) {
            if ( function_exists( 'dokan_get_seller_ids_by' ) ) {
                $seller_list = dokan_get_seller_ids_by( $order_id );
            } else {
                $seller_list = array_unique( array_keys( dokan_get_sellers_by( $order_id ) ) );
            }

            if ( count( $seller_list ) > 1 ) {
                $shop_address = "<br>" . $shop_address . "<br>" . '<i>' . __( 'From vendors:', 'dokan-invoice' ) . ' </i>';
                foreach ( $seller_list as $seller ) {
                    $vendor       = dokan()->vendor->get( $seller );
                    $shop_name    = $vendor->get_shop_name();
                    $shop_name    = ! empty( $shop_name ) ? $shop_name : __( 'store_info', 'dokan-invoice' );
                    $shop_address = $shop_address . "<div class='shop-name'><h3>" . $shop_name . "</h3></div>";
                }
            } else {
                $vendor_id      = $seller_list[0];
	            /**
	             * @since 1.2.1 added filter hook dokan_invoice_single_seller_address
	             */
                $shop_address   = "<br>" . apply_filters( 'dokan_invoice_single_seller_address', dokan_get_seller_address( $vendor_id ), $vendor_id, $order_id );
              }

            return $shop_address;
        } else {
            $vendor_id    = dokan_get_seller_id_by_order( $order_id );
	        /**
	         * @since 1.2.1 added filter hook dokan_invoice_single_seller_address
	         */
            $shop_address = "<br>" . apply_filters( 'dokan_invoice_single_seller_address', dokan_get_seller_address( $vendor_id ), $vendor_id, $order_id );

            return $shop_address;
        }
    }

    /**
     * Set seller permission true if oreder consists his item
     *
     * @param type $allowed
     * @param type $order_ids
     *
     * @return boolean
     */
    public function wpo_wcpdf_dokan_privs( $allowed, $order_ids ) {
        // check if user is seller
        if ( !$allowed && in_array( 'seller', $GLOBALS['current_user']->roles ) ) {

            if ( count( $order_ids ) == 1 ) {

                $order        = wc_get_order( $order_ids );
                $items        = $order->get_items();
                $seller_id    = dokan_get_seller_id_by_order( $order_ids );
                $current_user = get_current_user_id();

                if ( $current_user == $seller_id ) {
                    return true; // this seller is allowed
                } else {
                    return false;
                }
            }

            foreach ( $order_ids as $order_id ) {
                // get seller_id
                $seller_id     = dokan_get_seller_id_by_order( $order_id );
                // loop through items to get list of sellers for this order
                $order_sellers = array();
                $order         = new WC_Order( $order_id );
                $items         = $order->get_items();
                foreach ( $items as $item ) {
                    $item_seller = get_post_field( 'post_author', $item['product_id'] );
                    // check if item is from this seller
                    if ( $item_seller != $seller_id ) {
                        return true; // not allowed!
                    }
                }
            }
            // if we got here, that means the user is a seller and all orders and items belong to this seller
            return true; // allowed!
        } else {
            return $allowed; // preserve original check result
        }
    }

    /**
     * Get parent order id
     *
     * @param type $document
     *
     * @return array
     */
    public function get_order_id_parent_id( $document = null ) {
        if (empty($document) || empty($document->order)) {
            // PDF Invoice 1.X backwards compatibility
            global $wpo_wcpdf;
	        /**
	         * @var $order WC_Order
	         */
            $order     = $wpo_wcpdf->export->order;
            $order_id  = $order->get_id();
            $parent_id = $order->get_parent_id();
        } else {
            if ( $document->is_refund( $document->order ) ) {
                $order_id = $document->get_refund_parent_id( $document->order );
            } else {
                $order_id = $document->order_id;
            }
			$order = wc_get_order( $order_id );
            $parent_id = $order->get_parent_id();
        }

        return compact('order_id','parent_id');
    }

}

$dokan_invoice = Dokan_Invoice::init();
