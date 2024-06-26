<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce\Emails;

class QuoteFailed extends \WC_Email
{
    public function __construct()
    {

        $this->id             = 'quote_failed';
        $this->title          = __('Quote request failed', 'visual-renting-dynamics-sync');
        $this->description    = __('When an order failed to be sent to Visual Renting Dynamics Sync, this e-mail is sent to the shop administrator.', 'visual-renting-dynamics-sync'); // phpcs:ignore Generic.Files.LineLength.TooLong
        $this->heading        = __('Failed quote request', 'visual-renting-dynamics-sync');
        $this->subject        = __('Failed quote request', 'visual-renting-dynamics-sync');
        $this->template_html  = 'emails/admin-new-order.php';
        $this->template_plain = 'emails/plain/admin-new-order.php';
        $this->placeholders   = [
            '{order_date}'   => '',
            '{order_number}' => '',
        ];

        add_action('woocommerce_order_status_pending_to_quote-failed_notification', [$this, 'trigger']);
        add_action('woocommerce_order_status_failed_to_quote-failed_notification', [$this, 'trigger']);

        parent::__construct();

        $this->recipient = $this->get_option('recipient', get_option('admin_email'));
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject()
    {
        return __('[{site_title}]: Quote request failed #{order_number}', 'woocommerce');
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading()
    {
        return __('Failed quote request: #{order_number}', 'woocommerce');
    }


    /**
     * Trigger the sending of this email.
     *
     * @param int            $order_id The order ID.
     * @param WC_Order|false $order Order object.
     */
    public function trigger($order_id, $order = false)
    {
        $this->setup_locale();

        if ($order_id && ! is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }

        if (is_a($order, 'WC_Order')) {
            $this->object                         = $order;
            $this->placeholders['{order_date}']   = wc_format_datetime($this->object->get_date_created());
            $this->placeholders['{order_number}'] = $this->object->get_order_number();
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

            $order->update_meta_data('_quote_failed_email_sent', 'true');
            $order->save();
        }

        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
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
    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
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
    public function get_default_additional_content()
    {
        return __('Something went wrong during a quote request', 'woocommerce');
    }

    /**
     * Return content from the additional_content field.
     *
     * Displayed above the footer.
     *
     * @since 3.7.0
     * @return string
     */
    public function get_additional_content()
    {
        /**
         * This filter is documented in ./class-wc-email.php
         *
         * @since 7.8.0
         */
        return apply_filters('woocommerce_email_additional_content_' . $this->id, $this->format_string($this->get_option('additional_content')), $this->object, $this);
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields()
    {
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf(__('Available placeholders: %s', 'woocommerce'), '<code>' . implode('</code>, <code>', array_keys($this->placeholders)) . '</code>');
        $this->form_fields = array(
            'enabled'            => array(
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable this email notification', 'woocommerce'),
                'default' => 'yes',
            ),
            'recipient'          => array(
                'title'       => __('Recipient(s)', 'woocommerce'),
                'type'        => 'text',
                /* translators: %s: WP admin email */
                'description' => sprintf(__('Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce'), '<code>' . esc_attr(get_option('admin_email')) . '</code>'),
                'placeholder' => '',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'subject'            => array(
                'title'       => __('Subject', 'woocommerce'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ),
            'heading'            => array(
                'title'       => __('Email heading', 'woocommerce'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ),
            'additional_content' => array(
                'title'       => __('Additional content', 'woocommerce'),
                'description' => __('Text to appear below the main email content.', 'woocommerce') . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __('N/A', 'woocommerce'),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type'         => array(
                'title'       => __('Email type', 'woocommerce'),
                'type'        => 'select',
                'description' => __('Choose which format of email to send.', 'woocommerce'),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ),
        );
    }
}
