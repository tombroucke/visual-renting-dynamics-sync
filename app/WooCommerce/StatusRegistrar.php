<?php

namespace Otomaties\VisualRentingDynamicsSync\WooCommerce;

use Illuminate\Support\Str;

class StatusRegistrar
{
    private function mergeDefault(array $customStatus): array
    {
        $defaultArgs = [
            'label' => 'custom-status',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('%s <span class="count">(%s)</span>', '%s <span class="count">(%s)</span>'), // phpcs:ignore Generic.Files.LineLength.TooLong
        ];

        return array_merge($defaultArgs, $customStatus);
    }

    public function add(string $customStatusName, array $customStatus): self
    {
        $customStatus = $this->mergeDefault($customStatus);
        register_post_status($customStatusName, $customStatus);

        add_filter(
            'wc_order_statuses',
            function ($orderStatuses) use ($customStatusName, $customStatus) {
                return $this->addOrderStatuses(
                    $orderStatuses,
                    $customStatusName,
                    $customStatus['label']
                );
            }
        );

        add_filter(
            'bulk_actions-woocommerce_page_wc-orders',
            function ($bulkActions) use ($customStatusName, $customStatus) {
                return $this->addOrderStatuses(
                    $bulkActions,
                    $customStatusName,
                    sprintf(
                        __('Change status to %s', 'visual-renting-dynamics-sync'),
                        Str::lower($customStatus['label'])
                    )
                );
            },
            20,
            1
        );

        add_action(
            'handle_bulk_actions-woocommerce_page_wc-orders',
            function ($redirect, $action, $postIds) use ($customStatusName) {
                if ($action !== $customStatusName) {
                    return $redirect;
                }
                $this->updateOrderStatus($customStatusName, $postIds);

                return $redirect;
            },
            10,
            3
        );

        return $this;
    }

    public function addOrderStatuses(array $orderStatuses, string $customStatusName, string $label): array
    {
        $orderStatuses[$customStatusName] = $label;

        return $orderStatuses;
    }

    public function updateOrderStatus(string $customStatusName, array $postIds): void
    {
        foreach ($postIds as $postId) {
            wc_get_order($postId)
                ->update_status($customStatusName);
        }
    }
}
