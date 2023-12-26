<h3><?php _e('Shipping', 'visual-renting-dynamics-sync'); ?></h3>
<div class="vrd-fields__field-wrapper">
    <?php
        woocommerce_form_field(
            'vrd_shipping_method',
            [
                'type' => 'select',
                'label' => __('Shipping', 'visual-renting-dynamics-sync'),
                'required' => true,
                'options' => [
                    '' => __('Choose an option', 'woocommerce'),
                    'delivery' => __('Delivery', 'visual-renting-dynamics-sync'),
                    'pickup' => __('Pickup', 'visual-renting-dynamics-sync'),
                ],
                'input_class' => ['form-select'],
                'default' => WC()->session->get('vrd_shipping_method'),
            ]
        );

        woocommerce_form_field(
            'vrd_shipping_date',
            [
                'type' => 'date',
                'label' => __('Shipping date', 'visual-renting-dynamics-sync'),
                'required' => true,
                'input_class' => ['form-control'],
                'default' => WC()->session->get('vrd_shipping_date'),
            ]
        );

        woocommerce_form_field(
            'vrd_return_date',
            [
                'type' => 'date',
                'label' => __('Return date', 'visual-renting-dynamics-sync'),
                'required' => true,
                'input_class' => ['form-control'],
                'default' => WC()->session->get('vrd_return_date'),
            ]
        );
        ?>
</div>
