<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

use Otomaties\VisualRentingDynamicsSync\Api;
use Otomaties\VisualRentingDynamicsSync\Helpers\View;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\RentalProduct;

class Checkout
{
    private array $customFields = [];

    public function __construct(
        private Api $api,
        private Cart $cart
    ) {
    }

    public function runHooks() : self
    {
        $this->customFields = [
            'vrd_shipping_method' => __('Shipping method', 'visual-renting-dynamics-sync'),
            'vrd_shipping_date' => __('Shipping date', 'visual-renting-dynamics-sync'),
            'vrd_return_date' => __('Return date', 'visual-renting-dynamics-sync'),
        ];

        add_filter('woocommerce_checkout_posted_data', [$this, 'addCustomFieldsToPostedData']);
        add_filter('woocommerce_checkout_order_processed', [$this, 'requestOrder'], 10, 3);
        add_filter('woocommerce_endpoint_order-received_title', [$this, 'orderReceivedTitle'], 10, 2);
        add_filter('woocommerce_thankyou_order_received_text', [$this, 'orderReceivedText'], 10, 2);
        add_filter('woocommerce_email_heading_customer_processing_order', [$this, 'orderProcessingEmailHeading'], 10, 2); // phpcs:ignore Generic.Files.LineLength.TooLong
        add_filter('woocommerce_order_button_text', [$this, 'orderButtonText']);
        add_action('woocommerce_checkout_before_order_review_heading', [$this, 'addCustomFields']);
        add_action('woocommerce_checkout_process', [$this, 'validateCustomFields']);
        add_action('woocommerce_checkout_process', [$this, 'temporarilySaveCustomFields']);
        add_action('woocommerce_checkout_order_processed', [$this, 'removeTemporarilySavedCustomFields'], 999);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'saveCustomFields']);
        return $this;
    }

    public function addCustomFieldsToPostedData(array $postedData) : array
    {
        foreach ($this->customFields as $field => $label) {
            if (!empty($_POST[$field])) {
                $postedData[$field] = sanitize_text_field($_POST[$field]);
            }
        }

        return $postedData;
    }

    public function requestOrder(int $orderId, array $postedData, \WC_Order $order) : void
    {
        $args = [
            'naam' => $postedData['billing_first_name'] . ' ' . $postedData['billing_last_name'],
            'adres' => $postedData['billing_address_1'] . ' ' . $postedData['billing_address_2'],
            'postcode' => $postedData['billing_postcode'],
            'plaats' => $postedData['billing_city'],
            'telefoonMobiel' => $postedData['billing_phone'],
            'email' => $postedData['billing_email'],
            'memo' => $postedData['order_comments'],
            'leveringDatumTijd' => $postedData['vrd_shipping_date'],
            'afleveren' => $postedData['vrd_shipping_method'] === 'delivery',
            'retourneringDatumTijd' => $postedData['vrd_return_date'],
            'ophalen' => $postedData['vrd_shipping_method'] === 'delivery',
            'artikelen' => [],
        ];

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            if ($product instanceof RentalProduct) {
                $args['artikelen'][] = [
                    'artikelcode' => $product->get_sku(),
                    'aantal' => $item->get_quantity(),
                ];
            }
        }

        $response = $this->api->requestOrder($args);
        ray($response);
        $order->update_status('wc-quote-requested');
    }

    public function orderReceivedTitle(string $title, string $endpoint) : string
    {
        global $wp;

        if (!isset($wp->query_vars['order-received'])) {
            return $title;
        };
        
        $order = wc_get_order($wp->query_vars['order-received']);
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

        return __('We have received your quote request. We will contact you as soon as possible.', 'visual-renting-dynamics-sync'); // phpcs:ignore Generic.Files.LineLength.TooLong
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

    public function addCustomFields()
    {
        visualRentingDynamicSync()
            ->make(View::class)
            ->render('checkout/fields');
    }

    public function validateCustomFields()
    {
        foreach ($this->customFields as $field => $label) {
            if (empty($_POST[$field])) {
                $notice = '<strong>' . $label . '</strong> ' . __('is a required field.', 'visual-renting-dynamics-sync'); // phpcs:ignore Generic.Files.LineLength.TooLong
                wc_add_notice($notice, 'error');
            }
        }
    }

    public function temporarilySaveCustomFields()
    {
        foreach ($this->customFields as $field => $label) {
            if (!empty($_POST[$field])) {
                WC()->session->set($field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function removeTemporarilySavedCustomFields()
    {
        foreach ($this->customFields as $field => $label) {
            WC()->session->__unset($field);
        }
    }

    public function saveCustomFields(int $orderId) : void
    {
        $order = wc_get_order($orderId);
        foreach ($this->customFields as $field => $label) {
            if (!empty($_POST[$field])) {
                $order->update_meta_data($field, sanitize_text_field($_POST[$field]));
                $order->save();
            }
        }
    }
}
