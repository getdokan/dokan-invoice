<?php
/*
  Plugin Name: Dokan - PDF Invoice
  Plugin URI: https://wedevs.com/products/dokan/dokan-pdf-invoice/
  Description: A Dokan plugin Add-on to get PDF invoice.
  Version: 1.0
  Author: weDevs
  Author URI: https://wedevs.com/
  License: GPL2
  Text Domain: dokan-invoice
 */

/**
 * Copyright (c) 2015 weDevs (email: info@wedevs.com). All rights reserved.
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
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !class_exists( 'WooCommerce_PDF_Invoices_Export' ) ) {
    return ;
}

if ( !class_exists( 'WeDevs_Dokan' ) ) {
    return ;
}


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

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );

        //actions
        add_action( 'wp_ajax_dokan_get_invoice', array( $this, 'dokan_get_invoice_ajax' ) );
        add_action( 'wpo_wcpdf_process_template', array( $this, 'dokan_update_template_path' ) );

        //filters
        add_filter( 'wpo_wcpdf_listing_actions', array( $this, 'dokan_invoice_listing_actions' ), 10, 2 );
        add_filter( 'wpo_wcpdf_myaccount_actions', array( $this, 'dokan_invoice_listing_actions_my_account' ), 10, 2 );
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
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'dokan-invoice', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Set Dokan_invoice buttons on Woocommerce Order page
     *
     * Hooked with WP_invoice filter
     */
    function dokan_invoice_listing_actions( $listing_actions, $order ) {
        //$listing_actions = '';
        $listing_actions[ 'test' ] = array(
            'url' => wp_nonce_url( admin_url( 'admin-ajax.php?action=dokan_get_invoice&template_type=invoice&order_ids=' . $order->id ), 'dokan_get_invoice' ),
            'img' => WooCommerce_PDF_Invoices::$plugin_url . 'images/invoice.png',
            'alt' => __( 'Dokan PDF Invoice', 'dokan-invoice' ),
        );

        return $listing_actions;
    }

    /**
     * Set Dokan_invoice buttons on My Account page
     *
     * Hooked with WP_invoice filter
     */
    function dokan_invoice_listing_actions_my_account( $actions, $order ) {
        //$actions = '';
        if ( get_post_meta( $order->id, '_wcpdf_invoice_exists', true ) || in_array( $order->status, apply_filters( 'wpo_wcpdf_myaccount_allowed_order_statuses', array() ) ) ) {
            $actions[ 'invoice' ] = array(
                'url'  => wp_nonce_url( admin_url( 'admin-ajax.php?action=dokan_get_invoice&my-account&template_type=invoice_myaccount&order_ids=' . $order->id ), 'dokan_get_invoice' ),
                'name' => apply_filters( 'dokan_invoice_myaccount_button_text', __( 'Download invoice (PDF)', 'dokan-invoice' ) )
            );
        }


        return $actions;
    }

    /**
     * Generate PDF
     */
    function dokan_get_invoice_ajax() {
        global $wpo_wcpdf;

        $this->dokan_invoice_active = 1;

        // create a wp_invoice_export class object
        $wp_invoice_exp = new WooCommerce_PDF_Invoices_Export();

        //change the deafult paths to this plugins path
        $wp_invoice_exp->template_directory_name              = 'pdf';
        $wp_invoice_exp->template_base_path                   = (defined( 'WC_TEMPLATE_PATH' ) ? WC_TEMPLATE_PATH : $woocommerce->template_url) . $wp_invoice_exp->template_directory_name . '';
        $wp_invoice_exp->template_default_base_path           = Dokan_Invoice::$plugin_path . 'templates/' . $wp_invoice_exp->template_directory_name . '/';
        $wp_invoice_exp->template_default_base_uri            = Dokan_Invoice::$plugin_url . 'templates/' . $wp_invoice_exp->template_directory_name . '/';
        $wp_invoice_exp->template_settings[ 'template_path' ] = $wp_invoice_exp->template_default_base_path;

        // replace default export instant
        $wpo_wcpdf->export = $wp_invoice_exp;
        //generate pdf
        $wp_invoice_exp->generate_pdf_ajax();
    }

    /**
     * Change the deafault template path to Dokan_invoice Template path
     * if the request is sent from our plugin
     *
     * @uses load_plugin_textdomain()
     */
    function dokan_update_template_path() {
        if ( $this->dokan_invoice_active == 1 ) {
            global $wpo_wcpdf;

            $wpo_wcpdf->export->template_path = $wpo_wcpdf->export->template_default_base_path;
        }
    }

}

$dokan_invoice = Dokan_Invoice::init();
