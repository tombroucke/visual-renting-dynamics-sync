<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

use Otomaties\VisualRentingDynamicsSync\Helpers\View;

class Product
{
    public function runHooks() : self
    {
        add_filter('woocommerce_product_tabs', [$this, 'addDocumentTab']);
        add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'changeAddToCartText'], 10, 2);
        
        return $this;
    }
    
    public function addDocumentTab(array $tabs) : array
    {
        global $product;
        
        $documentIds = $product->get_meta('synced_documents');
        
        if (!$documentIds) {
            return $tabs;
        }
        
        $tabs['documents'] = [
            'title' => __('Documents', 'visual-renting-dynamics-sync'),
            'priority' => 50,
            'callback' => [$this, 'documentTabContent'],
        ];
        
        return $tabs;
    }
    
    public function documentTabContent() : void
    {
        global $product;
        
        $documentIds = $product->get_meta('synced_documents');
        
        if (!$documentIds) {
            return;
        }
        
        visualRentingDynamicSync()
            ->make(View::class)
            ->render(
                'single-product/tabs/documents',
                [
                    'documentIds' => $documentIds,
                ]
            );
    }

    public function changeAddToCartText($text, $product)
    {
        if ($product->get_type()) {
            $text = __('Add to quote request', 'visual-renting-dynamics-sync');
        }
        return $text;
    }
}
