<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

use Otomaties\VisualRentingDynamicsSync\Helpers\View;

class RentalProduct extends \WC_Product 
{    
    
    public function get_type() : string
    {
        return 'rental';
    }

    public static function addRentalProductFields() : void
    {
        global $post, $product_object;
        
        if (! $post) { 
            return; 
        }
        
        if ('product' != $post->post_type) {
            return;
        }
        
        $isRental = $product_object && 'rental' === $product_object->get_type() ? true : false;
        
        visualRentingDynamicSync()
            ->make(View::class)
            ->render(
                'admin/rental-product-fields', 
                compact('isRental')
            );
    }
    
    public static function addToCartButton() : void
    {
        do_action( 'woocommerce_simple_add_to_cart' );
    }
}
