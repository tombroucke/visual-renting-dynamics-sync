<script type='text/javascript'>
    jQuery(document).ready(function () {
        jQuery('.product_data_tabs .general_tab').addClass('show_if_rental');
        jQuery('#general_product_data .pricing').addClass('show_if_rental').show();
        <?php if ($isRental) { ?>
            jQuery('.product_data_tabs .general_tab').show();
            jQuery('#general_product_data .pricing').show();
        <?php } ?>
    });
</script>
