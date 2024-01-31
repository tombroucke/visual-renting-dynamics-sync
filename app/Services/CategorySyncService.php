<?php

namespace Otomaties\VisualRentingDynamicsSync\Services;

use Monolog\Logger;
use Otomaties\WpSyncPosts\Media;
use Otomaties\VisualRentingDynamicsSync\Api;
use Otomaties\VisualRentingDynamicsSync\Services\Contracts\Runnable;

class CategorySyncService implements Runnable
{
    public function __construct(private Api $api, private Logger $logger)
    {
    }

    public function run(array $args, array $assocArgs): void
    {
        $skipImages = $assocArgs['skip-images'];

        $this->syncCategories($skipImages);
        $this->syncSubcategories($skipImages);
        $this->syncSubSubCategories($skipImages);
    }

    private function syncCategories(bool $skipImages) : void
    {
        \WP_CLI::line("Fetching all categories from Visual Renting Dynamics API");
        $this->api->categories()
            ->reject(
                function ($category) {
                    return $category['categorienaam'] == 'Overige';
                }
            )
            ->each(
                function ($category) use ($skipImages) {
                    $args = [
                        'id' => 'category_' . $category['id'],
                        'name' => $category['categorienaam'],
                    ];
                    if (!$skipImages) {
                        $args['thumbnail_id'] = $this->fetchImagefromCategory($category);
                    }
                    $this->upsertTerm($args);
                }
            );
    }
    private function syncSubcategories(bool $skipImages) : void
    {
        \WP_CLI::line("Fetching all subcategories from Visual Renting Dynamics API");

        $this->api->subcategories()
            ->reject(
                function ($subCategory) {
                    return $subCategory['subcategorienaam'] == 'Overige';
                }
            )
            ->filter(
                function ($subCategory) {
                    $parentCategory = $this->findProductCategoriesByExternalId(
                        'category_' . $subCategory['categorieId']
                    );
                    return !is_wp_error($parentCategory) && !empty($parentCategory);
                }
            )
            ->each(
                function ($subCategory) use ($skipImages) {
                    $parentCategory = $this->findProductCategoriesByExternalId(
                        'category_' . $subCategory['categorieId']
                    );
                    $args = [
                        'id' => 'subcategory_' . $subCategory['id'],
                        'name' => $subCategory['subcategorienaam'],
                        'parent' => $parentCategory[0]->term_id,
                    ];

                    if (!$skipImages) {
                        $args['thumbnail_id'] = $this->fetchImagefromCategory($subCategory, 1);
                    }
                
                    $this->upsertTerm($args);
                }
            );
    }

    private function syncSubSubCategories(bool $skipImages) : void
    {
        \WP_CLI::line("Fetching all subsubcategories from Visual Renting Dynamics API");

        $this->api->subsubcategories()
            ->reject(
                function ($subSubCategory) {
                    return $subSubCategory['subsubcategorienaam'] == 'Overige';
                }
            )
            ->filter(
                function ($subSubCategory) {
                    $parentCategory = $this->findProductCategoriesByExternalId(
                        'subcategory_' . $subSubCategory['subcategorieId']
                    );
                    return !is_wp_error($parentCategory) && !empty($parentCategory);
                }
            )
            ->each(
                function ($subSubCategory) use ($skipImages) {
                    $parentCategory = $this->findProductCategoriesByExternalId(
                        'subcategory_' . $subSubCategory['subcategorieId']
                    );

                    $args = [
                        'id' => 'subsubcategory_' . $subSubCategory['id'],
                        'name' => $subSubCategory['subsubcategorienaam'],
                        'parent' => $parentCategory[0]->term_id,
                    ];

                    if (!$skipImages) {
                        $args['thumbnail_id'] = $this->fetchImagefromCategory($subSubCategory, 2);
                    }

                    $this->upsertTerm($args);
                }
            );
    }

    private function findProductCategoriesByExternalId(string $externalId) : array
    {
        return get_terms(
            [
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'meta_query' => [
                    [
                        'key' => 'external_id',
                        'value' => $externalId,
                    ],
                ],
            ]
        );
    }

    private function fetchImagefromCategory(array $category, int $depth = 0) : ?int
    {
        $endpoints = [
            [
                'name' => 'category',
                'key' => 'categorienaam',
            ],
            [
                'name' => 'subCategory',
                'key' => 'subcategorienaam',
            ],
            [
                'name' => 'subSubCategory',
                'key' => 'subsubcategorienaam',
            ],
        ];

        if (!$category['bevatAfbeelding']) {
            $categoryNameKey = $endpoints[$depth]['key'];
            $this->logger->info("Category {$category[$categoryNameKey]} ({$category['id']}) does not contain an image");
            return null;
        }

        $imageResponse = $this->api->{$endpoints[$depth]['name'] . 'Image'}($category['id']);
        $contentDisposition = $imageResponse->getHeader('Content-Disposition');
        $filename = str_replace("\"", "", explode('filename=', $contentDisposition[0])[1]);

        $image = new Media(
            [
                'url' => $this->api->{$endpoints[$depth]['name'] . 'ImageEndpoint'}($category['id']),
                'filestream' => $imageResponse->getBody()->getContents(),
                'filename' => $filename,
                'title' => strtok(pathinfo($filename, PATHINFO_FILENAME), '?'),
                'date_modified' => $category['afbeeldingLaatstGewijzigdOp'],
                'group' => 'synced_images'
            ]
        );

        $imageId = $image->importAndAttachToPost();

        if (!is_numeric($imageId)) {
            $this->logger->error("Error importing image for category {$category['categorienaam']} ({$category['id']})");
            return null;
        }

        return $imageId;
    }

    private function insertTerm(array $termArgs) : ?\WP_Term
    {
        $termArray = wp_insert_term($termArgs['name'], 'product_cat', $termArgs);

        if (is_wp_error($termArray)) {
            $this->logger->error("Error inserting term {$termArgs['name']}, error: {$termArray->get_error_message()}");
            return null;
        }

        update_term_meta($termArray['term_id'], 'external_id', $termArgs['id'], true);
        return get_term($termArray['term_id'], 'product_cat');
    }

    private function upsertTerm(array $termArgs) : ?\WP_Term
    {
        $foundTerms = $this->findProductCategoriesByExternalId($termArgs['id']);
        $term = empty($foundTerms) ? $this->insertTerm($termArgs) : $foundTerms[0];

        if (!$term) {
            return null;
        }

        wp_update_term($term->term_id, 'product_cat', $termArgs);

        if (isset($termArgs['thumbnail_id'])) {
            update_term_meta($term->term_id, 'thumbnail_id', $termArgs['thumbnail_id']);
        }

        return $term;
    }
}
