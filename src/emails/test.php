<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <p><?php _e( "Hello, An estimated shipment date has been added to your order:", 'woocommerce' ); ?></p>

    <p><?php _e( "For your reference, your order details are shown below.", 'woocommerce' ); ?></p>
<p>You Will Receive your email as sooon as stock gets back!!</p>

<?php


/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );