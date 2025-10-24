<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

use Otomaties\VisualRentingDynamicsSync\Helpers\View;

class RentalProduct extends \WC_Product
{
    public function get_type(): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return 'rental';
    }

    public static function addRentalProductFields(): void
    {
        global $post, $product_object;

        if (! $post) {
            return;
        }

        if ($post->post_type != 'product') {
            return;
        }

        $isRental = $product_object && $product_object->get_type() === 'rental' ? true : false;

        visualRentingDynamicSync()
            ->make(View::class)
            ->render(
                'admin/rental-product-fields',
                compact('isRental')
            );
    }

    public static function addToCartButton(): void
    {
        do_action('woocommerce_simple_add_to_cart');
    }

    public function get_price_html($deprecated = '')
    {
        if ($this->get_price() === '') {
            $price = apply_filters('woocommerce_empty_price_html', '', $this);
        } elseif ($this->is_on_sale()) {
            $price = wc_format_sale_price(wc_get_price_to_display($this, ['price' => $this->get_regular_price()]), wc_get_price_to_display($this)).$this->get_price_suffix();
        } else {
            $price = wc_price(wc_get_price_to_display($this)).$this->get_price_suffix();
        }

        $priceOnRequest = get_post_meta($this->get_id(), 'priceOnRequest', true);

        return ! $priceOnRequest ? apply_filters('woocommerce_get_price_html', $price, $this) : '<p class="price"><span class="amount">'.__('Price on request', 'visual-renting-dynamics-sync').'</span></p>';
    }
}
