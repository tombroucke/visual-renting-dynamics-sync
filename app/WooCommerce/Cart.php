<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

class Cart
{
    public function __construct(private ?\WC_Cart $cart)
    {
    }

    public function runHooks() : self
    {
        add_filter('woocommerce_cart_needs_shipping', [$this, 'cartNeedsShipping']);
        add_filter('woocommerce_cart_needs_payment', [$this, 'cartNeedsPayment']);
        add_filter('wc_add_to_cart_message_html', [$this, 'addToCartMessage'], 10, 3);
        add_filter('woocommerce_cart_item_price', [$this, 'cartItemPriceOnRequest'], 10, 3);
        add_filter('woocommerce_cart_item_subtotal', [$this, 'cartItemPriceOnRequest'], 10, 3);
        
        return $this;
    }

    public function cartNeedsShipping(bool $needsShipping) : bool
    {
        if (!$this->onlyRentalProductsInCart()) {
            return $needsShipping;
        }

        return false;
    }

    public function cartNeedsPayment(bool $needsPayment) : bool
    {
        if (!$this->onlyRentalProductsInCart()) {
            return $needsPayment;
        }

        return false;
    }

    public function onlyRentalProductsInCart() : bool
    {
        $productTypes = $this->productTypesInCart();
        if (count($productTypes) > 1 || !in_array('rental', $productTypes)) {
            return false;
        }

        return true;
    }

    public function productTypesInCart() : array
    {
        $productTypes = [];

        if (!$this->cart) {
            return $productTypes;
        }
        
        foreach ($this->cart->get_cart() as $cartItem) {
            $product = $cartItem['data'];
            if (!in_array($product->get_type(), $productTypes)) {
                $productTypes[] = $product->get_type();
            }
        }

        return $productTypes;
    }

    public function addToCartMessage($message, $products, $show_qty)
    {
        $titles = array();
        $count  = 0;
    
        if (! is_array($products)) {
            $products = array( $products => 1 );
            $show_qty = false;
        }
    
        if (! $show_qty) {
            $products = array_fill_keys(array_keys($products), 1);
        }
    
        foreach ($products as $product_id => $qty) {
            /* translators: %s: product name */
            $titles[] = apply_filters('woocommerce_add_to_cart_qty_html', ( $qty > 1 ? absint($qty) . ' &times; ' : '' ), $product_id) . apply_filters('woocommerce_add_to_cart_item_name_in_quotes', sprintf(_x('&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce'), strip_tags(get_the_title($product_id))), $product_id);
            $count   += $qty;
        }
    
        $titles = array_filter($titles);
        /* translators: %s: product name */
        $added_text = sprintf(_n('%s has been added to your quote request.', '%s have been added to your quote request.', $count, 'visual-renting-dynamics-sync'), wc_format_list_of_items($titles));
    
        // Output success messages.
        $wp_button_class = wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : '';
        if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
            $return_to = apply_filters('woocommerce_continue_shopping_redirect', wc_get_raw_referer() ? wp_validate_redirect(wc_get_raw_referer(), false) : wc_get_page_permalink('shop'));
            $message   = sprintf('<a href="%s" tabindex="1" class="button wc-forward%s">%s</a> %s', esc_url($return_to), esc_attr($wp_button_class), esc_html__('Continue shopping', 'woocommerce'), esc_html($added_text));
        } else {
            $message = sprintf('<a href="%s" tabindex="1" class="button wc-forward%s">%s</a> %s', esc_url(wc_get_cart_url()), esc_attr($wp_button_class), esc_html__('View quote request', 'visual-renting-dynamics-sync'), esc_html($added_text));
        }
    
        if (has_filter('wc_add_to_cart_message')) {
            wc_deprecated_function('The wc_add_to_cart_message filter', '3.0', 'wc_add_to_cart_message_html');
            $message = apply_filters('wc_add_to_cart_message', $message, $product_id);
        }
    
        return $message;
    }

    public function cartItemPriceOnRequest($price, $cart_item, $cart_item_key) : string
    {
        $product = $cart_item['data'];
        $priceOnRequest = get_post_meta($product->get_id(), 'priceOnRequest', true);
        if ($priceOnRequest) {
            return __('On request', 'visual-renting-dynamics-sync');
        }

        return $price;
    }
}
