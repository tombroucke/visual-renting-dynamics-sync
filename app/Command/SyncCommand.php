<?php

namespace Otomaties\VisualRentingDynamicsSync\Command;

use Otomaties\VisualRentingDynamicsSync\Services\ArticleSyncService;
use Otomaties\VisualRentingDynamicsSync\Services\CategorySyncService;
use Otomaties\VisualRentingDynamicsSync\Command\Contracts\CommandContract;

class SyncCommand implements CommandContract
{
    
    public const COMMAND_NAME = 'vrd sync';

    public const COMMAND_DESCRIPTION = 'Syncs all categories and articles from Visual Renting Dynamics';

    public const COMMAND_ARGUMENTS = [
        [
            'type' => 'assoc',
            'name' => 'article-limit',
            'description' => 'Limit the amount of articles to sync, defaults to 999999',
            'optional' => true,
        ],
        [
            'type' => 'flag',
            'name' => 'skip-images',
            'description' => 'Skip images when syncing articles and categories',
            'optional' => true,
        ]
    ];

    public function __construct(
        private CategorySyncService $categorySyncService,
        private ArticleSyncService $articleSyncService,
    )
    {
    }

    /**
     * Run below command to activate:
     *
     * wp vrd sync handle
     */
    public function handle(array $args, array $assocArgs): void
    {
        $defaultAssocArgs = [
            'article-limit' => null,
            'skip-images' => false,
        ];
        $assocArgs = array_merge($defaultAssocArgs, $assocArgs);

        $this->categorySyncService->run($args, $assocArgs);
        $this->articleSyncService->run($args, $assocArgs);

        \WP_CLI::success('Synced all categories and articles from Visual Renting Dynamics');
    }
}
