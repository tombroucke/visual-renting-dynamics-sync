<h3><?php _e('Delivery or pickup', 'visual-renting-dynamics-sync'); ?></h3>
<div class="vrd-fields__field-wrapper">
    <?php
    foreach ($fields as $fieldName => $fieldSettings) {
        woocommerce_form_field(
            $fieldName,
            $fieldSettings
        );
    }
    ?>
</div>
