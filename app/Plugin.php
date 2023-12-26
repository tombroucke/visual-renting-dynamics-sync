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
    public function runHooks()
    {
        add_action('init', [$this, 'loadTextDomain']);
        add_action('plugins_loaded', [$this, 'init']);
        add_action('woocommerce_init', [$this, 'initWoocommerce']);
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'visual-renting-dynamics-sync',
            false,
            dirname(plugin_basename(__FILE__), 2) . '/resources/languages'
        );
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

        $this->bind(
            Cart::class,
            function () {
                return new Cart(WC()->cart);
            }
        );

        $this->addRentalProductType();

        collect(
            [
            Cart::class,
            Checkout::class,
            Product::class
            ]
        )
            ->each(
                function ($class) {
                    $this->make($class)->runHooks();
                }
            );
    }

    public function addRentalProductType(): void
    {
        add_filter(
            'product_type_selector',
            function ($productTypes) {
                $productTypes['rental'] = __('Rental', 'visual-renting-dynamics-sync');
                return $productTypes;
            }
        );

        add_filter(
            'woocommerce_product_class',
            function ($className, $productType, $productId) {
                if ($productType === 'rental') {
                    return RentalProduct::class;
                }
                return $className;
            },
            10,
            3
        );

        add_action('admin_footer', [RentalProduct::class, 'addRentalProductFields']);
        add_action('woocommerce_rental_add_to_cart', [RentalProduct::class, 'addToCartButton']);
    }

    public function render(string $template, array $context = []): void
    {
        extract($context, EXTR_SKIP);
        include __DIR__ . "/../views/{$template}.php";
    }
}
