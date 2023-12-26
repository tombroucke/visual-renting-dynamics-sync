<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

use Otomaties\VisualRentingDynamicsSync\Helpers\View;

class Product
{
    public function runHooks() : self
    {
        add_filter('woocommerce_product_tabs', [$this, 'addDocumentTab']);
        
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
}
