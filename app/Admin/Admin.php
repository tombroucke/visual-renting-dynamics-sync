<?php

namespace Otomaties\VisualRentingDynamicsSync\Admin;

use Otomaties\VisualRentingDynamicsSync\Helpers\Assets;

class Admin
{
    public function __construct(
        private Assets $assets
    ) {
    }

    public function runHooks () : self
    {
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'addCustomFieldsToOrderDetails']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts'], 999);
        return $this;
    }

    public function enqueueScripts() {
        foreach ($this->assets->entrypoints()->admin->css as $css) {
            wp_enqueue_style(
                'vrd-admin-' . $css,
                $this->assets->url($css),
                [],
                null
            );
        }
    }

    public function addCustomFieldsToOrderDetails($order) {
        foreach (visualRentingDynamicSync()->make('custom-checkout-fields') as $key => $label) {
            $value = $order->get_meta($key);
            if ($value) {
                echo '<p><strong>' . $label . ':</strong><br />' . $value . '</p>';
            }
        }
    }
}
