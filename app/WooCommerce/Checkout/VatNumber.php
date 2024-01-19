<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce\Checkout;

class VatNumber
{

    public function runHooks()
    {
        add_filter('woocommerce_billing_fields', [$this, 'addVatNumberField']);
        add_action('woocommerce_checkout_process', [$this, 'validateVatNumber']);
        add_filter('woocommerce_order_formatted_billing_address', [$this, 'addVatNumberToAddress'], 10, 2 );
        add_filter('woocommerce_formatted_address_replacements', [$this, 'formattedAddressReplacements'], 10, 2 );
        add_filter('woocommerce_localisation_address_formats', [$this, 'localisationAddressFormat'], 10, 2 );
        add_filter('woocommerce_my_account_my_address_formatted_address', [$this, 'addVatNumberToAddressInMyAccount'], 10, 3);
        add_filter('woocommerce_checkout_posted_data', [$this, 'formatVatNumber']);
    }

    public function addVatNumberField(array $billingFields) : array
    {
        $billingFields['billing_vat_number'] = [
            'label' => __('VAT number', 'visual-renting-dynamics-sync'),
            'required' => false,
            'class' => ['form-row-wide'],
            'clear' => true,
            'priority' => 35
        ];

        return $billingFields;
    }

    public function validateVatNumber() {

        if (isset($_POST['billing_vat_number']) && $_POST['billing_vat_number'] != '') {

            $cleanVatNumber = $this->cleanVatNumber($_POST['billing_vat_number']);
            $validator = new \Ibericode\Vat\Validator();
            $validFormat = $validator->validateVatNumberFormat($cleanVatNumber);
            $validModulo97 = $this->validateModulo97(preg_replace("/[^0-9]/", "", $cleanVatNumber));
            if (!$validFormat || !$validModulo97) {
                wc_add_notice(__('This VAT number seems invalid', 'eetoile'), 'error');
            }
        }
    }

    public function addVatNumberToAddress($fields, $order)
    {
        $fields['vat_number'] = $order->get_meta('_billing_vat_number');
        return $fields;
    }

    public function formattedAddressReplacements($address, $args)
    {
        $address['{vat_number}'] = '';
        $address['{vat_vat_number_upper}']= '';

        if (! empty($args['vat_number'])) {
            $address['{vat_number}'] = $args['vat_number'];
            $address['{vat_vat_number_upper}'] = strtoupper($args['vat_number']);
        }
        return $address;
    }

    public function localisationAddressFormat($formats)
    {
        foreach ($formats as $country => $format) {
            $formats[$country] = str_replace("{company}\n", "{company}\n{vat_number}\n", $format);
        }
        return $formats;
    }

    public function addVatNumberToAddressInMyAccount($fields, $customer_id, $type)
    {
        if ($type == 'billing') {
            $fields['vat_number'] = get_user_meta($customer_id, 'billing_vat_number', true);
        }
    
        return $fields;
    }

    private function cleanVatNumber($vatNumber)
    {
        $vatNumber = str_replace(' ', '', $vatNumber);
        $vatNumber = str_replace('.', '', $vatNumber);
        $vatNumber = str_replace('-', '', $vatNumber);
        $vatNumber = str_replace(',', '', $vatNumber);
        $vatNumber = str_replace('/', '', $vatNumber);
        $vatNumber = strtoupper($vatNumber);
        return $vatNumber;
    }

    private function validateModulo97(string $vatNumber)
    {
        $checkDigits = (int)substr($vatNumber, 0, 8);
        $checkSum = (int)substr($vatNumber, -2);
        return 97 - ($checkDigits % 97) === $checkSum;
    }

    public function formatVatNumber($data) {
        if (isset($data['billing_vat_number'])) {
            $data['billing_vat_number'] = $this->cleanVatNumber($data['billing_vat_number']);
        }
        return $data;
    }
}
