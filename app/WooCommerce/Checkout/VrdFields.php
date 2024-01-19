<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce\Checkout;

use Otomaties\VisualRentingDynamicsSync\Helpers\View;

class VrdFields
{

    public function runHooks()
    {
        add_filter('woocommerce_checkout_posted_data', [$this, 'addCustomFieldsToPostedData']);
        add_action('woocommerce_checkout_before_order_review_heading', [$this, 'addCustomFields']);
        add_action('woocommerce_checkout_process', [$this, 'validateCustomFields']);
        add_action('woocommerce_checkout_process', [$this, 'temporarilySaveCustomFields']);
        add_action('woocommerce_checkout_order_processed', [$this, 'removeTemporarilySavedCustomFields'], 999);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'saveCustomFields']);
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

    public function addCustomFields()
    {
        visualRentingDynamicSync()
            ->make(View::class)
            ->render('checkout/fields', [
                'fields' => visualRentingDynamicSync()->make('custom-checkout-fields')
            ]);
    }

    public function validateCustomFields()
    {
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $fieldName => $fieldSettings) {
            if (empty($_POST[$fieldName])) {
                $notice = '<strong>' . $fieldSettings['label'] . '</strong> ' . __('is a required field.', 'visual-renting-dynamics-sync'); // phpcs:ignore Generic.Files.LineLength.TooLong
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
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $fieldName => $fieldSettings) {
            if (!empty($_POST[$fieldName])) {
                WC()->session->set($fieldName, sanitize_text_field($_POST[$fieldName]));
            }
        }
    }

    public function removeTemporarilySavedCustomFields()
    {
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $fieldName => $fieldSettings) {
            WC()->session->__unset($fieldName);
        }
    }

    public function saveCustomFields(int $orderId) : void
    {
        $order = wc_get_order($orderId);
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $fieldName => $fieldSettings) {
            if (!empty($_POST[$fieldName])) {
                $order->update_meta_data($fieldName, sanitize_text_field($_POST[$fieldName]));
                $order->save();
            }
        }
    }
}
