<?php

namespace Otomaties\VisualRentingDynamicsSync;

use Illuminate\Container\Container;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\Cart;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\Product;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\Checkout;
use Otomaties\VisualRentingDynamicsSync\Command\CommandRegistrar;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\RentalProduct;
use Otomaties\VisualRentingDynamicsSync\WooCommerce\StatusRegistrar;

class Plugin extends Container
{    
    public function runHooks() {
        add_action('plugins_loaded', [$this, 'init']);
        add_action('woocommerce_init', [$this, 'initWoocommerce']);
    }

    public function init(): void
    {
        new CommandRegistrar($this);

    }

    public function initWoocommerce(): void
    {
        (new StatusRegistrar())
            ->add(
                'wc-quote-requested', 
                ['label' => __('Quote requested', 'visual-renting-dynamics-sync')]
            );

        $this->bind(Cart::class, function () {
            return new Cart(WC()->cart);
        });

        $this->addRentalProductType();

        $this->make(Cart::class)->runHooks();
        $this->make(Checkout::class)->runHooks();
        $this->make(Product::class)->runHooks();
    }

    public function addRentalProductType(): void
    {
        add_filter('product_type_selector', function ($productTypes) {
            $productTypes['rental'] = __('Rental', 'visual-renting-dynamics-sync');
            return $productTypes;
        });

        add_filter('woocommerce_product_class', function ($className, $productType, $productId) {
            if ($productType === 'rental') {
                return RentalProduct::class;
            }
            return $className;
        }, 10, 3);

        add_action('admin_footer', [RentalProduct::class, 'addRentalProductFields']);
        add_action('woocommerce_rental_add_to_cart', [RentalProduct::class, 'addToCartButton']);
    }
}
