<?php

namespace Otomaties\VisualRentingDynamicsSync;

use Monolog\Logger;
use Illuminate\Support\Collection;

class Api
{

    const API_URL = 'https://webapi.rentaldynamics.nl';
    
    const API_VERSION = 'v3';

    public function __construct(private string $apikey, private Logger $logger)
    {
    }

    public function articles() : Collection
    {
        $endpoint = '/artikelen';
        return collect($this->get($endpoint));
    }

    public function articleImage(string $sku, int $imageNumber) : array
    {
        $endpoint = '/artikelen/' . $sku . '/afbeeldingen/' . $imageNumber;
        return $this->get($endpoint, [], 'image');
    }

    public function articleImageEndpoint(string $sku, int $imageNumber) : string
    {
        $endpoint = '/artikelen/' . $sku . '/afbeeldingen/' . $imageNumber;
        return self::API_URL . '/' . self::API_VERSION . $endpoint;
    }

    public function articleDocument(string $sku, int $documentId) : array
    {
        $endpoint = '/artikelen/' . $sku . '/documenten/' . $documentId;
        return $this->get($endpoint, [], 'image');
    }

    public function articleDocumentEndpoint(string $sku, int $documentId) : string
    {
        $endpoint = '/artikelen/' . $sku . '/documenten/' . $documentId;
        return self::API_URL . '/' . self::API_VERSION . $endpoint;
    }

    public function categories() : Collection
    {
        $endpoint = '/categorieen';
        return collect($this->get($endpoint));
    }

    public function categoryImage(string $categoryId) : array
    {
        $endpoint = '/categorieen/' . $categoryId . '/afbeelding';
        return $this->get($endpoint, [], 'image');
    }

    public function categoryImageEndpoint(string $categoryId) : string
    {
        $endpoint = '/categorieen/' . $categoryId . '/afbeelding';
        return self::API_URL . '/' . self::API_VERSION . $endpoint;
    }

    public function subcategories() : Collection
    {
        $endpoint = '/subcategorieen';
        return collect($this->get($endpoint));
    }

    public function subcategoryImage(string $subcategoryId) : array
    {
        $endpoint = '/subcategorieen/' . $subcategoryId . '/afbeelding';
        return $this->get($endpoint, [], 'image');
    }

    public function subcategoryImageEndpoint(string $subcategoryId) : string
    {
        $endpoint = '/subcategorieen/' . $subcategoryId . '/afbeelding';
        return self::API_URL . '/' . self::API_VERSION . $endpoint;
    }

    public function subsubcategories() : Collection
    {
        $endpoint = '/subsubcategorieen';
        return collect($this->get($endpoint));
    }

    public function subsubcategoryImage(string $subsubcategoryId) : array
    {
        $endpoint = '/subsubcategorieen/' . $subsubcategoryId . '/afbeelding';
        return $this->get($endpoint, [], 'image');
    }

    public function subsubcategoryImageEndpoint(string $subsubcategoryId) : string
    {
        $endpoint = '/subsubcategorieen/' . $subsubcategoryId . '/afbeelding';
        return self::API_URL . '/' . self::API_VERSION . $endpoint;
    }

    public function requestOrder(array $params) : array
    {
        $endpoint = '/aanvraagorder/';
        return $this->post($endpoint, $params);
    }

    private function url(string $endpoint, array $params = []) : string
    {
        $url = self::API_URL . '/' . self::API_VERSION . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    private function headers() : array
    {
        return [
            'x-Api-Key' => $this->apikey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function get(string $endpoint, array $params = [], string $returnType = 'json') : mixed
    {
        $args = [
            'headers' => $this->headers(),
        ];

        $response = $this->backoffRetry(function () use ($endpoint, $params, $args) {
            return wp_remote_get($this->url($endpoint, $params), $args);
        });
        
        if ($returnType === 'json') {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(json_last_error_msg());
            }
            return $data;
        }
        return $response;
    }

    private function post(string $endpoint, array $params = []) : mixed
    {
        $args = [
            'headers' => $this->headers(),
            'body' => json_encode($params),
        ];

        $response = $this->backoffRetry(function () use ($endpoint, $args) {
            return wp_remote_post($this->url($endpoint), $args);
        });
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(json_last_error_msg());
        }
        return $data;
    }

    private function backoffRetry(callable $callback, int $maxAttempts = 5) : mixed
    {
        $attempts = 0;
        do {
            try {
                return $callback();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                sleep(pow(2, $attempts));
            }
        } while (++$attempts < $maxAttempts);
        throw new \Exception('Maximum number of attempts reached');
    }
}
