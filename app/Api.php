<?php

namespace Otomaties\VisualRentingDynamicsSync;

use Monolog\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\RequestException;

class Api
{

    const API_URL = 'https://webapi.rentaldynamics.nl';
    
    const API_VERSION = 'v3';

    const MAX_RETRIES = 2;

    public function __construct(private string $apikey, private Client $client, private Logger $logger)
    {
    }

    public function articles() : Collection
    {
        return $this->collectJson($this->get('/artikelen'));
    }

    public function articleImage(string $sku, int $imageNumber) : Response
    {
        return $this->get("/artikelen/{$sku}/afbeeldingen/{$imageNumber}");
    }

    public function articleImageEndpoint(string $sku, int $imageNumber) : string
    {
        return $this->url("/artikelen/{$sku}/afbeeldingen/{$imageNumber}");
    }

    public function articleDocument(string $sku, int $documentId) : Response
    {
        return $this->get("/artikelen/{$sku}/documenten/{$documentId}");
    }

    public function articleDocumentEndpoint(string $sku, int $documentId) : string
    {
        return $this->url("/artikelen/{$sku}/documenten/{$documentId}");
    }

    public function categories() : Collection
    {
        return $this->collectJson($this->get('/categorieen'));
    }

    public function categoryImage(string $categoryId) : Response
    {
        return $this->get("/categorieen/{$categoryId}/afbeelding");
    }

    public function categoryImageEndpoint(string $categoryId) : string
    {
        return $this->url("/categorieen/{$categoryId}/afbeelding");
    }

    public function subcategories() : Collection
    {
        return $this->collectJson($this->get('/subcategorieen'));
    }

    public function subcategoryImage(string $subcategoryId) : Response
    {
        return $this->get("/subcategorieen/{$subcategoryId}/afbeelding");
    }

    public function subcategoryImageEndpoint(string $subcategoryId) : string
    {
        return $this->url("/subcategorieen/{$subcategoryId}/afbeelding");
    }

    public function subsubcategories() : Collection
    {
        return $this->collectJson($this->get('/subsubcategorieen'));
    }

    public function subsubcategoryImage(string $subsubcategoryId) : Response
    {
        return $this->get("/subsubcategorieen/{$subsubcategoryId}/afbeelding");
    }

    public function subsubcategoryImageEndpoint(string $subsubcategoryId) : string
    {
        return $this->url("/subsubcategorieen/{$subsubcategoryId}/afbeelding");
    }

    public function requestOrder(array $params) : Response
    {
        return $this->post('/aanvraagorder/', $params);
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

    private function collectJson(Response $response) : Collection
    {
        return collect(
            json_decode(
                $response->getBody()
                    ->getContents(),
                true
            )
        );
    }

    private function get(string $endpoint, array $params = [], $retryCount = 0) : mixed
    {
        try {
            $response = $this->client->request(
                'GET',
                $this->url($endpoint, $params),
                [
                'headers' => $this->headers(),
                ]
            );
        } catch (RequestException $e) {
            return $this->handleException($e, [$this, 'get'], $endpoint, $params, $retryCount);
        }
        return $response;
    }

    private function post(string $endpoint, array $params = [], $retryCount = 0) : mixed
    {
        try {
            $response = $this->client->request(
                'POST',
                $this->url($endpoint),
                [
                    'headers' => $this->headers(),
                    'json' => $params,
                ]
            );
        } catch (RequestException $e) {
            return $this->handleException($e, [$this, 'post'], $endpoint, $params, $retryCount);
        }

        return $response;
    }

    private function handleException(RequestException $e, callable $method, string $endpoint, array $params, int $retryCount) : mixed // phpcs:ignore Generic.Files.LineLength.TooLong
    {
        $this->logger->error(
            $e->getMessage(),
            [
            'method' => $method,
            'endpoint' => $endpoint,
            'params' => $params,
            'retryCount' => $retryCount,
            ]
        );

        if ($retryCount < self::MAX_RETRIES) {
            $retryCount++;
            return $method($endpoint, $params, $retryCount);
        }
        
        throw new RequestException(
            $e->getMessage(),
            $e->getRequest(),
            $e->getResponse(),
            $e->getPrevious(),
            $e->getHandlerContext()
        );

        return false;
    }
}
