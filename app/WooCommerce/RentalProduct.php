<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

class RentalProduct extends \WC_Product 
{    
    
    public function get_type() : string
    {
        return 'rental';
    }

    public static function addRentalProductFields() : void
    {
        global $post, $product_object;
        
        if ( ! $post ) { return; }
        
        if ( 'product' != $post->post_type ) :
            return;
        endif;
        
        $isRental = $product_object && 'rental' === $product_object->get_type() ? true : false;
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                jQuery('.product_data_tabs .general_tab').addClass('show_if_rental');
                jQuery('#general_product_data .pricing').addClass('show_if_rental').show();
                <?php if ( $isRental ) { ?>
                    jQuery('.product_data_tabs .general_tab').show();
                jQuery('#general_product_data .pricing').show();
                <?php } ?>
            });
        </script>
        <?php
    }
    
    public static function addToCartButton() : void
    {
        do_action( 'woocommerce_simple_add_to_cart' );
    }
}
