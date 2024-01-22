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
    }

    public function showVrdOrderDetails($order)
    {
        $fields = [];

        collect(visualRentingDynamicSync()->make('custom-checkout-fields'))
            ->each(function ($fieldSettings, $fieldName) use (&$fields, $order) {
                $fields[] = [
                    'name' => $fieldSettings['label'],
                    'value' => $this->beautifyFieldValue($fieldName, $order->get_meta($fieldName), $fieldSettings)
                ];
            });
        
        visualRentingDynamicSync()->make(View::class)->render('order/order-details', [
            'order' => $order,
            'fields' => $fields
        ]);
    }

    public function beautifyFieldValue($fieldName, $value, $fieldSettings)
    {

        // Shipping method
        if ('vrd_shipping_method' === $fieldName && isset($fieldSettings['options'][$value])) {
            return $fieldSettings['options'][$value];
        }

        // Date fields
        if (strpos($fieldName, '_date') !== false) {
            $dateTime = DateTime::createFromFormat('Y-m-d', $value);
            return date_i18n(get_option('date_format'), $dateTime->format('U'));
        }

        return $value;
    }
}
