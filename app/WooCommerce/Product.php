<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

class Product
{
    public function runHooks() : self
    {
        add_filter('woocommerce_product_tabs', [$this, 'addDocumentTab']);

        return $this;
    }

    public function addDocumentTab(array $tabs) : array
    {
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

        echo '<ul class="documents">';
        foreach ($documentIds as $documentId) {
            $attachment = wp_get_attachment_url($documentId);
            echo '<li><a href="' . $attachment . '" target="_blank">' . get_the_title($documentId) . '</a></li>';
        }
        echo '</ul>';
    }
}
