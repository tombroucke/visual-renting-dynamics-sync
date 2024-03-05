<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce\Checkout;

use DateTime;
use Otomaties\VisualRentingDynamicsSync\Helpers\View;

class OrderDetails
{
    public function runHooks()
    {
        add_filter('woocommerce_order_details_after_order_table', [$this, 'showVrdOrderDetails']);
        add_action('woocommerce_email_order_meta', [$this, 'showVrdOrderDetails'], 10);
        add_filter('woocommerce_order_formatted_line_subtotal', [$this, 'priceOnRequest'], 10, 3);
    }

    public function showVrdOrderDetails($order)
    {
        $fields = visualRentingDynamicSync()->make('custom-checkout-fields')
            ->pluck('fields')
            ->flatmap(function ($item) {
                return $item;
            })
            ->map(function ($field, $fieldName) use (&$fields, $order) {
                return [
                    'label' => $field['label'],
                    'value' => $this->beautifyFieldValue($fieldName, $order->get_meta($fieldName), $field)
                ];
            })
            ->reject(function ($field) {
                return empty($field['value']) || $field['value'] === '';
            });
        
        visualRentingDynamicSync()->make(View::class)->render('order/order-details', [
            'order' => $order,
            'fields' => apply_filters('visual_renting_dynamics_order_details_fields', $fields, $order)
        ]);
    }

    public function beautifyFieldValue($fieldName, $value, $fieldSettings)
    {
        if (empty($value)) {
            return $value;
        }

        if ('vrd_shipping_method' === $fieldName && isset($fieldSettings['options'][$value])) {
            return $fieldSettings['options'][$value];
        }

        if (strpos($fieldName, '_date') !== false) {
            $dateTime = DateTime::createFromFormat('Y-m-d', $value);
            return date_i18n(get_option('date_format'), $dateTime->format('U'));
        }

        return $value;
    }

    public function priceOnRequest($subtotal, $item, $order)
    {
        $product = $item->get_product();

        if (!$product) {
            return $subtotal;
        }

        if ($product->get_type() !== 'rental') {
            return $subtotal;
        }

        $priceOnRequest = get_post_meta($product->get_id(), 'priceOnRequest', true);
        if ($priceOnRequest) {
            return __('On request', 'visual-renting-dynamics-sync');
        }

        return $subtotal;
    }
}
