<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

class Account
{
    public function runHooks(): self
    {
        add_filter('woocommerce_account_menu_items', [$this, 'renameOrders'], 10, 2);
        add_filter('woocommerce_account_orders_columns', [$this, 'removeOrderIdFromColumns']);

        return $this;
    }

    public function renameOrders(array $items, array $endpoints): array
    {
        $items['orders'] = __('Quote requests', 'visual-renting-dynamics-sync');

        return $items;
    }

    public function removeOrderIdFromColumns(array $columns): array
    {
        unset($columns['order-number']);

        return $columns;
    }
}
