<?php
/**
 * Plugin Name: Dokan - PDF Invoice
 * Plugin URI: https://dokan.co/wordpress/
 * Description: A Dokan plugin Add-on to get PDF invoice.
 * Version: 1.2.8
 * Author: weDevs
 * Author URI: https://dokan.co/
 * License: GPL2
 * Text Domain: dokan-invoice
 * WC requires at least: 8.5.0
 * WC tested up to: 9.8.5
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
            'notice' => sprintf( esc_html__( '%sDokan PDF Invoice%s requires %sPDF Invoices & packing slips for WooCommerce plugin%s to be installed & activated!' , 'dokan-invoice' ), '<b>', '</b>', '<a target="_blank" href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">', '</a>' ),
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
        if ( ! class_exists( 'WeDevs_Dokan' ) || ! class_exists( $this->wc_pdf_class ) ) {
            return;
        }

        add_filter( 'dokan_my_account_my_sub_orders_actions', array( $this, 'dokan_invoice_listing_actions_my_account' ), 50, 2 );
        add_action( 'wpo_wcpdf_after_shop_name', array( $this, 'print_dokan_shop_name' ), 10, 2 );
        add_filter( 'wpo_wcpdf_shop_address', array( $this,'wpo_wcpdf_add_dokan_shop_details'), 10, 2 );
        add_filter( 'wpo_wcpdf_check_privs', array( $this,'wpo_wcpdf_dokan_privs'), 50, 2 );
        add_filter( 'wpo_wcpdf_current_user_is_allowed', array( $this, 'restrict_vendor_document_permission' ), 50 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'dokan_rest_prepare_shop_order_object', array( $this, 'add_invoice_url_to_rest_response' ), 10, 2 );
    }

    /**
     * Add invoice action(s) to the Dokan orders REST response.
     * @since 1.2.8
     *
     * @param WP_REST_Response $response
     * @param WC_Order         $order
     *
     * @return WP_REST_Response
     */
    public function add_invoice_url_to_rest_response( $response, $order ) {
        if ( ! is_a( $response, 'WP_REST_Response' ) || ! $order || ! function_exists( 'WPO_WCPDF' ) || ! function_exists( 'wcpdf_get_document' ) ) {
            return $response;
        }

        $document_types = array( 'invoice', 'packing-slip' );

        $data    = $response->get_data();
        $actions = isset( $data['actions'] ) && is_array( $data['actions'] ) ? $data['actions'] : array();

        foreach ( $document_types as $document_type ) {
            $document = wcpdf_get_document( $document_type, $order );
            if ( ! $document || ! $document->is_enabled() ) {
                continue;
            }

            $url = $this->is_base_v6()
                ? WPO_WCPDF()->get_instance( 'endpoint' )->get_document_link( $order, $document_type )
                : WPO_WCPDF()->endpoint->get_document_link( $order, $document_type );
            $url = esc_url_raw( wp_specialchars_decode( $url, ENT_QUOTES ) );
            if ( empty( $url ) ) {
                continue;
            }

            $actions[ $document_type ] = array(
                'url' => $url,
            );
        }

        $data['actions'] = $actions;
        $response->set_data( $data );

        return $response;
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
        wp_enqueue_script( 'dokan-invoice-orders', plugins_url( 'assets/js/dokan-orders.js', __FILE__ ), array( 'wp-hooks' ), date( 'Ymd' ), false );
    }

    /**
     * Set Dokan_invoice buttons on My Account page
     *
     * Hooked with WP_invoice filter
     *
     * @param array    $actions List of actions
     * @param \WC_Order $order   Order object
     *
     * @return array $actions
     */
    public function dokan_invoice_listing_actions_my_account( $actions, $order ) {
	    if ( class_exists( '\WPO\WC\PDF_Invoices\Frontend' ) ) {
		    $frontend = new \WPO\WC\PDF_Invoices\Frontend();

		    if ( method_exists( $frontend, 'my_account_pdf_link' ) ) {
			    return $frontend->my_account_pdf_link( $actions, $order );
		    }

		    if ( method_exists( $frontend, 'my_account_invoice_pdf_link' ) ) {
                return $frontend->my_account_invoice_pdf_link( $actions, $order );
            }
	    }

        $frontend = $this->is_base_v6()
            ? WPO_WCPDF()->get_instance( 'frontend' )
            : WPO_WCPDF()->frontend;

        /**
         * Remove there method my_account_invoice_pdf_link and replace with my_account_invoice_actions;
         * 
         * @see https://github.com/wpovernight/woocommerce-pdf-invoices-packing-slips/pull/1149/files
         * @see https://github.com/wpovernight/woocommerce-pdf-invoices-packing-slips/commit/f6251e2452e502ca837471fbf1de6eb2a6b638d0
         */

        if ( method_exists( $frontend, 'my_account_invoice_pdf_link' ) ) {
            return $frontend->my_account_invoice_pdf_link( $actions, $order );
        }

        if ( method_exists( $frontend, 'my_account_invoice_actions' ) ) {
            return $frontend->my_account_invoice_actions( $actions, $order );
        }

	    return $actions;
    }

    /**
     * Print the vendor store name after the shop name.
     *
     * @param string             $document_type
     * @param WC_Abstract_Order  $order
     *
     * @return void
     */
    public function print_dokan_shop_name( $document_type, $order ) {
        if ( ! $order instanceof WC_Abstract_Order ) {
            return;
        }

        if ( $order instanceof WC_Order_Refund ) {
            $order = wc_get_order( $order->get_parent_id() );

            if ( ! $order ) {
                return;
            }
        }

        $order_id = $order->get_id();

        // Parent order: only show the vendor when there is a single one.
        if ( 0 === (int) $order->get_parent_id() ) {
            $seller_list = function_exists( 'dokan_get_seller_ids_by' )
                ? dokan_get_seller_ids_by( $order_id )
                : array_unique( array_keys( dokan_get_sellers_by( $order_id ) ) );

            if ( 1 !== count( $seller_list ) ) {
                return;
            }

            $vendor_id = reset( $seller_list );
        } else {
            $vendor_id = dokan_get_seller_id_by_order( $order_id );
        }

        $vendor     = dokan()->vendor->get( $vendor_id );
        $store_name = $vendor ? $vendor->get_shop_name() : '';

        if ( empty( $store_name ) ) {
            return;
        }

        $document = wcpdf_get_document( $document_type, $order );
        $label    = apply_filters( 'dokan_invoice_shop_name_label', esc_html__( 'Vendor', 'dokan-invoice' ), $document );

        printf(
            '<div class="dokan-vendor-name">%s: %s</div>',
            esc_html( $label ),
            esc_html( $store_name )
        );
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
     * Allow vendors to access documents of their own orders.
     *
     * @param bool  $allowed
     * @param array $order_ids
     *
     * @return bool
     */
    public function wpo_wcpdf_dokan_privs( $allowed, $order_ids ) {
        if ( $allowed || ! is_user_logged_in() || empty( $order_ids ) || ! is_array( $order_ids ) || ! function_exists( 'dokan_is_seller_has_order' ) ) {
            return $allowed;
        }

        if ( ! current_user_can( 'dokan_view_order' ) || ! $this->request_has_valid_access_key( $order_ids ) ) {
            return $allowed;
        }

        $vendor_id = dokan_get_current_user_id();

        foreach ( $order_ids as $order_id ) {
            if ( ! dokan_is_seller_has_order( $vendor_id, absint( $order_id ) ) ) {
                return $allowed;
            }
        }

        return true;
    }

    /**
     * Vendors must not inherit full document permission from edit_shop_orders.
     *
     * @param bool $allowed
     *
     * @return bool
     */
    public function restrict_vendor_document_permission( $allowed ) {
        if ( $allowed && current_user_can( 'dokandar' ) && ! current_user_can( 'manage_woocommerce' ) ) {
            return false;
        }

        return $allowed;
    }

    /**
     * Get parent order id
     *
     * @param type $document
     *
     * @return array
     */
    public function get_order_id_parent_id( $document = null ) {
        if ( empty( $document ) || empty( $document->order ) ) {
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
                $order_id = $document->order->get_id();
            }
			$order     = wc_get_order( $order_id );
            $parent_id = $order->get_parent_id();
        }

        return compact( 'order_id', 'parent_id' );
    }

    /**
     * Check whether PDF Invoices is running version 6.0.0 or later.
     *
     * @return bool
     */
    public function is_base_v6(): bool {
        static $is_v6 = null;

        if ( null !== $is_v6 ) {
            return $is_v6;
        }

        if ( ! function_exists( 'WPO_WCPDF' ) ) {
            return false;
        }

        $wpo_ips_base_version = preg_replace(
            '/[^0-9.].*$/',
            '',
            (string) ( WPO_WCPDF()->version ?? '' )
        );

        if ( empty( $wpo_ips_base_version ) ) {
            return false;
        }

        $is_v6 = version_compare( $wpo_ips_base_version, '6.0.0', '>=' );

        return $is_v6;
    }

    /**
     * Whether the document request carries a valid nonce, or the order key of its single order.
     *
     * @param array $order_ids
     *
     * @return bool
     */
    protected function request_has_valid_access_key( array $order_ids ) {
        $access_key = '';

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        foreach ( array( 'access_key', '_wpnonce', 'order_key' ) as $key ) {
            if ( ! empty( $_REQUEST[ $key ] ) && is_string( $_REQUEST[ $key ] ) ) {
                $access_key = sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) );
                break;
            }
        }

        $action = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
        // phpcs:enable

        if ( '' === $access_key ) {
            return false;
        }

        if ( '' !== $action && wp_verify_nonce( $access_key, $action ) ) {
            return true;
        }

        // Full access type: a single order link carries that order's key.
        if ( 1 === count( $order_ids ) ) {
            $order = wc_get_order( absint( reset( $order_ids ) ) );

            if ( $order instanceof WC_Order_Refund ) {
                $order = wc_get_order( $order->get_parent_id() );
            }

            return $order instanceof WC_Order && hash_equals( $order->get_order_key(), $access_key );
        }

        return false;
    }

}

$dokan_invoice = Dokan_Invoice::init();
