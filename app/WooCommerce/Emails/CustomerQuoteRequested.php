<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce\Emails;

class CustomerQuoteRequested extends \WC_Email
{
    public function __construct()
    {

        $this->id             = 'customer_quote_requested';
        $this->customer_email = true;
        
        $this->title          = __('Quote requested', 'visual-renting-dynamics-sync');
        $this->description    = __( 'This is a quote request notification sent to customers containing order details.', 'visual-renting-dynamics-sync' );
        $this->heading        = __('Quote requested', 'visual-renting-dynamics-sync');
        $this->subject        = __('Quote requested', 'visual-renting-dynamics-sync');
        $this->template_html  = 'emails/customer-processing-order.php';
        $this->template_plain = 'emails/plain/customer-processing-order.php';
        $this->placeholders   = array(
            '{order_date}'   => '',
            '{order_number}' => '',
        );

        add_action( 'woocommerce_order_status_pending_to_quote-requested_notification', array( $this, 'trigger' ) );
        add_action( 'woocommerce_order_status_failed_to_quote-requested_notification',  array( $this, 'trigger' ) );

        parent::__construct();
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
        return __( 'We received your quote request!', 'visual-renting-dynamics-sync' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
        return __( 'Thank you for your quote request', 'visual-renting-dynamics-sync' );
    }

    public function trigger( $order_id, $order = false ) {
        $this->setup_locale();

        if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( is_a( $order, 'WC_Order' ) ) {
            $this->object                         = $order;
            $this->recipient                      = $this->object->get_billing_email();
            $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
            $this->placeholders['{order_number}'] = $this->object->get_order_number();
        }

        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
            )
        );
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
            )
        );
    }

    /**
     * Default content to show below main email content.
     *
     * @since 3.7.0
     * @return string
     */
    public function get_default_additional_content() {
        return __( 'Thanks for using {site_url}!', 'woocommerce' );
    }
}
