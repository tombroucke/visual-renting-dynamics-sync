<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Otomaties\VisualRentingDynamicsSync\Api;

final class ApiTest extends TestCase
{
    public function testIfGetRequestRetriesAfterFailure() {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->expects($this->exactly(3))->method('request')->willThrowException(new \GuzzleHttp\Exception\RequestException('Error Communicating with Server', new \GuzzleHttp\Psr7\Request('GET', 'test')));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->expectException(\GuzzleHttp\Exception\RequestException::class);
        $api->articles();
    }
    
    public function testArticlesReturnsCollection(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $data = [
            [
                "artikelcode" => "000050000",
                "omschrijving" => "Set praattafel + hoes zwart",
                "omschrijvingUitgebreid" => "",
                "informatieInternetKort" => "",
                "informatieInternet" => "",
                "categorieId" => 16,
                "subcategorieId" => 85,
                "subsubcategorieId" => 85,
                "soort" => "Verhuurartikel",
                "lengte" => 0.0,
                "breedte" => 0.0,
                "hoogte" => 0.0,
                "gewicht" => 0.0,
                "diameter" => 0.0,
                "inhoud" => 0.0,
                "omvangTransport" => 0.0,
                "kleur" => "",
                "eenheid" => "",
                "btwPercentage" => 21.0,
                "prijs" => 15.0,
                "prijsOpAanvraagInternet" => false,
                "bevatAfbeelding1" => true,
                "bevatAfbeelding2" => false,
                "bevatAfbeelding3" => false,
                "afbeelding1LaatstGewijzigdOp" => "2021-09-20T11:42:58.307",
                "afbeelding2LaatstGewijzigdOp" => "2018-10-26T05:10:43.103",
                "afbeelding3LaatstGewijzigdOp" => "2018-10-26T05:10:43.117",
                "publicerenInternet" => true,
                "isArtikelset" => true,
                "isToebehoren" => false,
                "isLosLeverbaar" => true,
                "documenten" => [],
                "alternatieven" => [],
                "relaties" => [],
                "verhuurprijzenOverige" => [],
                "setcomponenten" => [],
                "toebehoren" => [],
                "tags" => [],
            ]
        ];
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], json_encode($data)));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $articles = $api->articles();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $articles);
        $this->assertEquals($articles->first()['artikelcode'], '000050000');
    }
    
    public function testArticleImageReturnsResponse(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $api->articleImage('000050000', 1));
    }
    
    public function testArticleImageEndpointIsCorrect(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertEquals($api->articleImageEndpoint('000050000', 1), 'https://webapi.rentaldynamics.nl/v3/artikelen/000050000/afbeeldingen/1');
    }

    public function testArticleDocumentReturnsResponse(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $api->articleDocument('000050000', 1));
    }

    public function testArticleDocumentEndpointIsCorrect(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertEquals($api->articleDocumentEndpoint('000050000', 1), 'https://webapi.rentaldynamics.nl/v3/artikelen/000050000/documenten/1');
    }

    public function testCategoriesReturnsCollection(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $data = [
            [
                "id" => 9,
                "categorienaam" => "Springkastelen en attracties",
                "bevatAfbeelding" => true,
                "afbeeldingLaatstGewijzigdOp" => "2023-02-14T16:01:11.823",
            ],
            [
                "id" => 26,
                "categorienaam" => "Klank & Licht",
                "bevatAfbeelding" => true,
                "afbeeldingLaatstGewijzigdOp" => "2023-03-06T09:32:42.037",
            ]
        ];
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], json_encode($data)));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $categories = $api->categories();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $categories);
        $this->assertEquals($categories->first()['id'], 9);
    }

    public function testCategoryImageReturnsResponse(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $api->categoryImage('9'));
    }

    public function testCategoryImageEndpointIsCorrect(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertEquals($api->categoryImageEndpoint('9'), 'https://webapi.rentaldynamics.nl/v3/categorieen/9/afbeelding');
    }

    public function testSubcategoriesReturnsCollection(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $data = [
            [
                "id" => 9,
                "categorienaam" => "Springkastelen en attracties",
                "bevatAfbeelding" => true,
                "afbeeldingLaatstGewijzigdOp" => "2023-02-14T16:01:11.823",
            ],
            [
                "id" => 26,
                "categorienaam" => "Klank & Licht",
                "bevatAfbeelding" => true,
                "afbeeldingLaatstGewijzigdOp" => "2023-03-06T09:32:42.037",
            ]
        ];
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], json_encode($data)));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $subcategories = $api->subcategories();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $subcategories);
        $this->assertEquals($subcategories->last()['id'], 26);
    }

    public function testSubcategoryImageReturnsResponse(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $api->subcategoryImage('9'));
    }

    public function testSubcategoryImageEndpointIsCorrect(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertEquals($api->subcategoryImageEndpoint('9'), 'https://webapi.rentaldynamics.nl/v3/subcategorieen/9/afbeelding');
    }

    public function testSubSubCategoriesReturnsCollection(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $data = [
            [
                "id" => 9,
                "categorienaam" => "Springkastelen en attracties",
                "bevatAfbeelding" => true,
                "afbeeldingLaatstGewijzigdOp" => "2023-02-14T16:01:11.823",
            ],
            [
                "id" => 26,
                "categorienaam" => "Klank & Licht",
                "bevatAfbeelding" => true,
                "afbeeldingLaatstGewijzigdOp" => "2023-03-06T09:32:42.037",
            ]
        ];
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], json_encode($data)));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $subsubcategories = $api->subsubcategories();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $subsubcategories);
        $this->assertEquals($subsubcategories->last()['id'], 26);
    }

    public function testSubsubcategoryImageReturnsResponse(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $api->subsubcategoryImage('9'));
    }

    public function testSubsubcategoryImageEndpointIsCorrect(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $this->assertEquals($api->subsubcategoryImageEndpoint('9'), 'https://webapi.rentaldynamics.nl/v3/subsubcategorieen/9/afbeelding');
    }

    public function testRequestOrderCanBeMade() {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockClient->expects($this->once())->method('request')->willReturn(new \GuzzleHttp\Psr7\Response(200, [], ''));
        $api = new Api('apikey', $mockClient, new \Monolog\Logger('test'));
        $api->requestOrder([
            'naam' => 'test',
            'adres' => 'test',
            'postcode' => 'test',
            'plaats' => 'test',
            'telefoonMobiel' => 'test',
            'email' => 'test',
            'memo' => 'test',
        ]);
    }
}
