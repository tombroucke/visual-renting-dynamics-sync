<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

use Otomaties\VisualRentingDynamicsSync\Api;
use Otomaties\VisualRentingDynamicsSync\Helpers\Assets;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\Checkout\OrderDetails;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\Checkout\VatNumber;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\Checkout\VrdFields;

class Checkout
{
    public function __construct(
        private Api $api,
        private Cart $cart,
        private Assets $assets
    ) {
        collect([
            OrderDetails::class,
            VatNumber::class,
            VrdFields::class,
        ])
            ->map(fn ($class) => new $class)
            ->each(fn ($class) => $class->runHooks());
    }

    public function runHooks(): self
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts'], 999);

        add_filter('woocommerce_valid_order_statuses_for_payment_complete', [$this, 'removeFailedFromValidOrderStatusesForPaymentComplete']); // phpcs:ignore Generic.Files.LineLength.TooLong
        add_filter('woocommerce_checkout_order_processed', [$this, 'requestOrder'], 9999, 3);

        add_filter('woocommerce_endpoint_order-received_title', [$this, 'orderReceivedTitle'], 10, 2);
        add_filter('woocommerce_thankyou_order_received_text', [$this, 'orderReceivedText'], 10, 2);
        add_filter('woocommerce_email_heading_customer_processing_order', [$this, 'orderProcessingEmailHeading'], 10, 2); // phpcs:ignore Generic.Files.LineLength.TooLong
        add_filter('woocommerce_order_button_text', [$this, 'orderButtonText']);
        add_filter('woocommerce_cart_needs_shipping_address', '__return_true');
        add_filter('woocommerce_checkout_fields', [$this, 'orderCommentMaxLength'], 9999);

        return $this;
    }

    public function enqueueScripts()
    {
        if (! function_exists('is_checkout') || ! is_checkout()) {
            return;
        }

        if (property_exists($this->assets->entrypoints()->checkout, 'js')) {
            foreach ($this->assets->entrypoints()->checkout->js as $js) {
                wp_enqueue_script('vrd-checkout-'.$js, $this->assets->url($js), [], null, true);

                $localize = apply_filters('visual_renting_dynamics_checkout_localize', [
                    'shipping_date' => [
                        'min_date' => 'today',
                        'disabled_days' => [],
                    ],
                    'return_date' => [
                        'min_date' => 'today',
                        'disabled_days' => [],
                    ],
                ], $js);
                wp_localize_script('vrd-checkout-'.$js, 'vrd_checkout_vars', $localize);
            }
        }

        if (property_exists($this->assets->entrypoints()->checkout, 'css')) {
            foreach ($this->assets->entrypoints()->checkout->css as $css) {
                wp_enqueue_style('vrd-checkout-'.$css, $this->assets->url($css), [], null);
            }
        }
    }

    public function removeFailedFromValidOrderStatusesForPaymentComplete(array $statuses): array
    {
        return array_diff($statuses, ['failed']);
    }

    public function requestOrder(int $orderId, array $postedData, \WC_Order $order): void
    {
        $shippingDate = \DateTime::createFromFormat('Y-m-d', $postedData['vrd_shipping_date']);
        $returnDate = \DateTime::createFromFormat('Y-m-d', $postedData['vrd_return_date']);
        $clientReference = $postedData['vrd_client_reference'] ?? null;
        $isDelivery = $order->get_meta('vrd_shipping_method') === 'delivery';
        $name = $order->get_billing_first_name().' '.$order->get_billing_last_name();

        $countries_instance = new \WC_Countries;
        $billingCountryCode = $order->get_billing_country();

        $args = [
            'naam' => $order->get_billing_company() ? $order->get_billing_company() : $name,
            'contactpersoon' => $name,
            'adres' => $order->get_billing_address_1().' '.$order->get_billing_address_2(),
            'postcode' => $order->get_billing_postcode(),
            'plaats' => $order->get_billing_city(),
            'land' => $countries_instance->get_countries()[$billingCountryCode] ?? $billingCountryCode,
            'telefoonMobiel' => $order->get_billing_phone(),
            'email' => $order->get_billing_email(),
            'memo' => $order->get_customer_note(),
            'leveringDatumTijd' => $shippingDate ? $shippingDate->format('Y-m-d') : null,
            'afleveren' => $isDelivery,
            'retourneringDatumTijd' => $returnDate ? $returnDate->format('Y-m-d') : null,
            'ophalen' => $isDelivery,
            'artikelen' => [],
        ];

        if ($clientReference) {
            $args['referentieKlant'] = $clientReference;
        }

        if ($order->get_meta('_billing_vat_number')) {
            $args['btwNummer'] = $order->get_meta('_billing_vat_number');
        }

        if ($isDelivery) {
            $shippingCountryCode = $order->get_shipping_country();
            $args['contactpersoonLevering'] = $name;
            $args['adresLevering'] = $order->get_shipping_address_1().' '.$order->get_shipping_address_2();
            $args['postcodeLevering'] = $order->get_shipping_postcode();
            $args['plaatsLevering'] = $order->get_shipping_city();
            $args['landLevering'] = $countries_instance->get_countries()[$shippingCountryCode] ?? $shippingCountryCode;
        }

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
            $response = $this->api->requestOrder(
                apply_filters('visual_renting_dynamics_orderaanvraag_args', $args, $order)
            );
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

    public function orderReceivedTitle(string $title, string $endpoint): string
    {
        global $wp;

        if (! isset($wp->query_vars['order-received'])) {
            return $title;
        }

        $order = wc_get_order($wp->query_vars['order-received']);

        if ($endpoint === 'order-received' && $order && $order->has_status('quote-requested')) {
            return __('Thank you for your quote request', 'visual-renting-dynamics-sync');
        }

        if ($endpoint === 'order-received' && $order && $order->has_status('quote-failed')) {
            return __('Something went wrong.', 'visual-renting-dynamics-sync');
        }

        return $title;
    }

    public function orderReceivedText(string $text, ?\WC_Order $order): string
    {
        if (! $order) {
            return $text;
        }

        if ($order->has_status('quote-requested')) {
            return __('We have received your quote request. We will contact you as soon as possible.', 'visual-renting-dynamics-sync'); // phpcs:ignore Generic.Files.LineLength.TooLong
        }

        if ($order->has_status('quote-failed')) {
            return __('Something went wrong during your quote request. Please contact us.', 'visual-renting-dynamics-sync'); // phpcs:ignore Generic.Files.LineLength.TooLong
        }

        return $text;
    }

    public function orderProcessingEmailHeading(string $heading, \WC_Order $order): string
    {
        if (! $order->has_status('quote-requested')) {
            return $heading;
        }

        return __('Your quote request is being processed', 'visual-renting-dynamics-sync');
    }

    public function orderButtonText(string $text): string
    {
        return $this->cart->onlyRentalProductsInCart() ? __('Request quote', 'visual-renting-dynamics-sync') : $text;
    }

    public function orderCommentMaxLength($fields)
    {
        $limit = 5000;
        $fields['order']['order_comments']['label'] = $fields['order']['order_comments']['label'].sprintf(' '.__('(max. %s characters)', 'visual-renting-dynamics-sync'), $limit); // phpcs:ignore Generic.Files.LineLength.TooLong
        $fields['order']['order_comments']['maxlength'] = $limit;

        return $fields;
    }
}
