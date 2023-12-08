<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

use Otomaties\VisualRentingDynamicsSync\Api;

class Checkout
{
    public function __construct(
        private Api $api,
        private Cart $cart
    )
    {
    }

    public function runHooks() : self
    {
        add_filter('woocommerce_checkout_order_processed', [$this, 'requestOrder'], 10, 3);
        add_filter('woocommerce_endpoint_order-received_title', [$this, 'orderReceivedTitle'], 10, 2);
        add_filter('woocommerce_thankyou_order_received_text', [$this, 'orderReceivedText'], 10, 2);
        add_filter('woocommerce_email_heading_customer_processing_order', [$this, 'orderProcessingEmailHeading'], 10, 2);
        add_filter('woocommerce_order_button_text', [$this, 'orderButtonText']);

        return $this;
    }

    public function requestOrder(int $orderId, array $postedData, \WC_Order $order ) : void
    {
        $args = [
            'naam' => $postedData['billing_first_name'] . ' ' . $postedData['billing_last_name'],
            'adres' => $postedData['billing_address_1'] . ' ' . $postedData['billing_address_2'],
            'postcode' => $postedData['billing_postcode'],
            'plaats' => $postedData['billing_city'],
            'telefoonMobiel' => $postedData['billing_phone'],
            'email' => $postedData['billing_email'],
            'memo' => $postedData['order_comments'],
        ];

        $response = $this->api->requestOrder($args);
        $order->update_status('wc-quote-requested');
    }

    public function orderReceivedTitle(string $title, string $endpoint) : string
    {
        global $wp;

        if (!isset($wp->query_vars['order-received'])) {
            return $title;
        };
        
        $order = wc_get_order( $wp->query_vars['order-received'] );
        if ($endpoint !== 'order-received' || !$order->has_status('quote-requested')) {
            return $title;
        }

        return __('Thank you for your quote request', 'visual-renting-dynamics-sync');
    }

    public function orderReceivedText(string $text, \WC_Order $order) : string
    {
        if (!$order->has_status('quote-requested')) {
            return $text;
        }

        return __('We have received your quote request. We will contact you as soon as possible.', 'visual-renting-dynamics-sync');
    }

    public function orderProcessingEmailHeading(string $heading, \WC_Order $order) : string
    {
        if (!$order->has_status('quote-requested')) {
            return $heading;
        }

        return __('Your quote request is being processed', 'visual-renting-dynamics-sync');
    }

    public function orderButtonText(string $text) : string
    {
        return $this->cart->onlyRentalProductsInCart() ? __('Request quote', 'visual-renting-dynamics-sync') : $text;
    }
}
