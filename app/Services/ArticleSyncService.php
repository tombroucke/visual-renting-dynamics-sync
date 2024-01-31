<?php

namespace Otomaties\VisualRentingDynamicsSync\Services;

use Monolog\Logger;
use Otomaties\WpSyncPosts\Syncer;
use Otomaties\VisualRentingDynamicsSync\Api;
use Otomaties\VisualRentingDynamicsSync\Services\Contracts\Runnable;

class ArticleSyncService implements Runnable
{
    public function __construct(private Api $api, private Logger $logger)
    {
    }

    public function run(array $args, array $assocArgs): void
    {
        \WP_CLI::line(
            sprintf("Fetching %s products from Visual Renting Dynamics API", $assocArgs['article-limit'] ?? 'all')
        );

        $syncer = new Syncer('product');

        $this->api->articles()
            ->filter(
                function ($article) {
                    return $article['publicerenInternet'];
                }
            )
            ->take($assocArgs['article-limit'])
            ->each(
                function ($article) use ($syncer, $assocArgs) {
                    $args = [
                        'post_title' => $article['omschrijving'],
                        'post_content' => $article['omschrijvingUitgebreid'],
                        'meta_input' => [
                            'short_description_internet' => $article['informatieInternetKort'],
                            'description_internet' => $article['informatieInternet'],
                            'category_id' => $article['categorieId'],
                            'subcategory_id' => $article['subcategorieId'],
                            'subsubcategory_id' => $article['subsubcategorieId'],
                            'kind' => $article['soort'],
                            'diameter' => $article['diameter'],
                            'content' => $article['inhoud'],
                            'sizeTransport' => $article['omvangTransport'],
                            'color' => $article['kleur'],
                            'unit' => $article['eenheid'],
                            'vatPercentage' => $article['btwPercentage'],
                            'priceOnRequest' => $article['prijsOpAanvraagInternet'],
                            'publishInternet' => $article['publicerenInternet'],
                            'is_article_set' => $article['isArtikelset'],
                            'is_accessory' => $article['isToebehoren'],
                            'is_separately_available' => $article['isLosLeverbaar'],
                            'alternatives' => [],
                            'relations' => [],
                            'rental_prices' => $article['verhuurprijzenOverige'],
                            'setcomponents' => $article['setcomponenten'],
                            'accessories' => $article['toebehoren'],
                            'tags' => []
                        ],
                        'woocommerce' => [
                            'product_type' => 'rental',
                            'meta_input' => [
                                '_sku' => $article['artikelcode'],
                                '_price' => $article['prijs'],
                                '_regular_price' => $article['prijs'],
                                '_length' => $article['lengte'],
                                '_width' => $article['breedte'],
                                '_height' => $article['hoogte'],
                                '_weight' => $article['gewicht'],
                            ],
                        ],
                    ];

                    collect($article['alternatieven'])
                    ->each(
                        function ($alternative) use (&$args) {
                            $args['meta_input']['alternatives'][] = $alternative['artikelcodeAlternatief'];
                        }
                    );

                    collect($article['relaties'])
                    ->each(
                        function ($relation) use (&$args) {
                            $args['meta_input']['relations'][] = $relation['artikelcodeRelatie'];
                        }
                    );

                    collect($article['tags'])
                    ->each(
                        function ($tag) use (&$args) {
                            $args['meta_input']['tags'][] = $tag['tag'];
                        }
                    );
                
                    $media = [];

                    if (!$assocArgs['skip-images']) {
                        collect(
                            [
                                'bevatAfbeelding1',
                                'bevatAfbeelding2',
                                'bevatAfbeelding3',
                            ]
                        )->filter(
                            function ($imageKey) use ($article) {
                                return $article[$imageKey];
                            }
                        )->each(
                            function ($imageKey, $key) use ($article, &$media) {
                                $imageResponse = $this->api->articleImage($article['artikelcode'], ++$key);
                                $contentDisposition = $imageResponse->getHeader('Content-Disposition');
                                $filename = str_replace("\"", "", explode('filename=', $contentDisposition[0])[1]);
    
                                $media[] = [
                                    'url' => $this->api->articleImageEndpoint($article['artikelcode'], $key),
                                    'filestream' => $imageResponse->getBody()->getContents(),
                                    'filename' => $filename,
                                    'title' => strtok(pathinfo($filename, PATHINFO_FILENAME), '?'),
                                    'date_modified' => $article['afbeelding' . $key . 'LaatstGewijzigdOp'],
                                    'group'  => 'synced_images'
                                ];
                            }
                        );
    
                        if (!empty($media)) {
                            $media[0]['featured'] = true;
                        }
                    }

                    collect($article['documenten'])
                        ->each(
                            function ($document, $key) use (&$media) {
                                $documentResponse = $this->api->articleDocument(
                                    $document['artikelcode'],
                                    $document['id']
                                );
                                $filename = $document['bestandsnaam'];

                                $media[] = [
                                    'url' => $this->api->articleDocumentEndpoint($document['artikelcode'], $document['id']),
                                    'filestream' => $documentResponse->getBody()->getContents(),
                                    'filename' => $filename,
                                    'title' => strtok(pathinfo($filename, PATHINFO_FILENAME), '?'),
                                    'date_modified' => $document['bestandLaatstGewijzigdOp'],
                                    'group'  => 'synced_documents'
                                ];
                            }
                        );

                    $args['media'] = $media;
    
                    $existingPostQuery = [
                        'by'    => 'meta_value',
                        'key'   => '_sku',
                        'value' => $article['artikelcode'],
                    ];
                
                    $syncer->addProduct($args, $existingPostQuery);
                }
            );

        $products = $syncer->execute();

        foreach ($products as $product) {
            $product = wc_get_product($product->id());

            if (!$assocArgs['skip-images']) {
                $syncedImages = $product->get_meta('synced_images', true);
            
                if (is_array($syncedImages)) {
                    array_shift($syncedImages);
                    $product->update_meta_data('_product_image_gallery', implode(',', $syncedImages));
                    $product->save();
                }
            }

            $categoryKeys = array_reverse([
                'category',
                'subcategory',
                'subsubcategory',
            ]);

            $externalCategoryIds = [];
            foreach ($categoryKeys as $categoryKey) {
                $externalCategoryIds[] = $categoryKey . '_' . $product->get_meta($categoryKey . '_id', true);
            }

            if (count($externalCategoryIds) > 0) {
                $terms = get_terms(
                    [
                    'taxonomy' => 'product_cat',
                    'hide_empty' => false,
                    'meta_query' => [
                        [
                            'key' => 'external_id',
                            'value' => $externalCategoryIds,
                            'compare' => 'IN',
                        ],
                    ],
                    ]
                );
                if (!empty($terms)) {
                    $termIds = array_map(
                        function ($term) {
                            return $term->term_id;
                        },
                        $terms
                    );
                    wp_set_object_terms($product->get_ID(), $termIds, 'product_cat');
                }
            }
        }
    }
}
