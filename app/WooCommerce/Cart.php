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
}
