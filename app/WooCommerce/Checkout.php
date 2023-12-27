<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

use Otomaties\VisualRentingDynamicsSync\Api;
use Otomaties\VisualRentingDynamicsSync\Helpers\View;
use Otomaties\VisualRentingDynamicsSync\Helpers\Assets;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\RentalProduct;

class Checkout
{

    public function __construct(
        private Api $api,
        private Cart $cart,
        private Assets $assets
    ) {
    }

    public function runHooks() : self
    {        
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts'], 999);
        
        add_filter('woocommerce_checkout_posted_data', [$this, 'addCustomFieldsToPostedData']);
        add_action('woocommerce_checkout_before_order_review_heading', [$this, 'addCustomFields']);
        add_action('woocommerce_checkout_process', [$this, 'validateCustomFields']);
        add_action('woocommerce_checkout_process', [$this, 'temporarilySaveCustomFields']);
        add_action('woocommerce_checkout_order_processed', [$this, 'removeTemporarilySavedCustomFields'], 999);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'saveCustomFields']);

        add_filter('woocommerce_valid_order_statuses_for_payment_complete', [$this, 'removeFailedFromValidOrderStatusesForPaymentComplete']);
        add_filter('woocommerce_checkout_order_processed', [$this, 'requestOrder'], 9999, 3);

        add_filter('woocommerce_endpoint_order-received_title', [$this, 'orderReceivedTitle'], 10, 2);
        add_filter('woocommerce_thankyou_order_received_text', [$this, 'orderReceivedText'], 10, 2);
        add_filter('woocommerce_email_heading_customer_processing_order', [$this, 'orderProcessingEmailHeading'], 10, 2); // phpcs:ignore Generic.Files.LineLength.TooLong
        add_filter('woocommerce_order_button_text', [$this, 'orderButtonText']);


        return $this;
    }

    public function enqueueScripts() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }
        
        foreach ($this->assets->entrypoints()->checkout->js as $js) {
            wp_enqueue_script('vrd-checkout-' . $js, $this->assets->url($js), [], null, true);
        }
        
        foreach ($this->assets->entrypoints()->checkout->css as $css) {
            wp_enqueue_style('vrd-checkout-' . $css, $this->assets->url($css), [], null);
        }
    }

    public function addCustomFieldsToPostedData(array $postedData) : array
    {
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $field => $label) {
            if (!empty($_POST[$field])) {
                $postedData[$field] = sanitize_text_field($_POST[$field]);
            }
        }

        return $postedData;
    }

    public function removeFailedFromValidOrderStatusesForPaymentComplete(array $statuses) : array
    {
        return array_diff($statuses, ['failed']);
    }

    public function requestOrder(int $orderId, array $postedData, \WC_Order $order) : void
    {
        $shippingDate = \DateTime::createFromFormat('Y-m-d', $postedData['vrd_shipping_date']);
        $returnDate = \DateTime::createFromFormat('Y-m-d', $postedData['vrd_return_date']);

        $args = [
            'naam' => $postedData['billing_first_name'] . ' ' . $postedData['billing_last_name'],
            'adres' => $postedData['billing_address_1'] . ' ' . $postedData['billing_address_2'],
            'postcode' => $postedData['billing_postcode'],
            'plaats' => $postedData['billing_city'],
            'telefoonMobiel' => $postedData['billing_phone'],
            'email' => $postedData['billing_email'],
            'memo' => $postedData['order_comments'],
            'leveringDatumTijd' => $shippingDate ? $shippingDate->format('Y-m-d\TH:i') : null,
            'afleveren' => $postedData['vrd_shipping_method'] === 'delivery',
            'retourneringDatumTijd' => $returnDate ? $returnDate->format('Y-m-d\TH:i') : null,
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
        try {
            $response = $this->api->requestOrder($args);
            if ($response->getStatusCode() === 200) {
                $order->update_status('wc-quote-requested');
            }
        } catch (\Exception $e) {
            $order->update_status('wc-quote-failed');
            $order->add_order_note(
                sprintf(
                    /* translators: %s: error message */
                    __('Error requesting quote: %s', 'visual-renting-dynamics-sync'),
                    $e->getMessage()
                )
            );
        }
    }

    public function orderReceivedTitle(string $title, string $endpoint) : string
    {
        global $wp;

        if (!isset($wp->query_vars['order-received'])) {
            return $title;
        };
        
        $order = wc_get_order($wp->query_vars['order-received']);

        if ($endpoint === 'order-received' && $order->has_status('quote-requested')) {
            return __('Thank you for your quote request', 'visual-renting-dynamics-sync');
        }

        if ($endpoint === 'order-received' && $order->has_status('quote-failed')) {
            return __('Something went wrong.', 'visual-renting-dynamics-sync');
        }

        return $title;
    }

    public function orderReceivedText(string $text, \WC_Order $order) : string
    {
        if ($order->has_status('quote-requested')) {
            return __('We have received your quote request. We will contact you as soon as possible.', 'visual-renting-dynamics-sync'); // phpcs:ignore Generic.Files.LineLength.TooLong
        }

        if ($order->has_status('quote-failed')) {
            return __('Something went wrong during your quote request. Please contact us.', 'visual-renting-dynamics-sync'); // phpcs:ignore Generic.Files.LineLength.TooLong
        }

        return $text;

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
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $field => $label) {
            if (empty($_POST[$field])) {
                $notice = '<strong>' . $label . '</strong> ' . __('is a required field.', 'visual-renting-dynamics-sync'); // phpcs:ignore Generic.Files.LineLength.TooLong
                wc_add_notice($notice, 'error');
            }
        }
        
        if (isset($_POST['vrd_shipping_date']) && isset($_POST['vrd_return_date'])) {
            $shippingDate = \DateTime::createFromFormat('Y-m-d', $_POST['vrd_shipping_date']);
            $returnDate = \DateTime::createFromFormat('Y-m-d', $_POST['vrd_return_date']);
            if ($shippingDate >= $returnDate) {
                $notice = __('Return date must be after shipping date.', 'visual-renting-dynamics-sync');
                wc_add_notice($notice, 'error');
            }
        }

        if (strlen($_POST['billing_first_name'] . ' ' . $_POST['billing_last_name']) >= 150) {
            $notice = __('Name must be less than 150 characters.', 'visual-renting-dynamics-sync');
            wc_add_notice($notice, 'error');
        }
    }

    public function temporarilySaveCustomFields()
    {
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $field => $label) {
            if (!empty($_POST[$field])) {
                WC()->session->set($field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function removeTemporarilySavedCustomFields()
    {
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $field => $label) {
            WC()->session->__unset($field);
        }
    }

    public function saveCustomFields(int $orderId) : void
    {
        $order = wc_get_order($orderId);
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $field => $label) {
            if (!empty($_POST[$field])) {
                $order->update_meta_data($field, sanitize_text_field($_POST[$field]));
                $order->save();
            }
        }
    }
}
