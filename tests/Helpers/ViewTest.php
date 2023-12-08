<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Otomaties\VisualRentingDynamicsSync\Helpers\View;

final class ViewTest extends TestCase
{
    public function testIncorrectPathThrowsException(): void
    {
        $this->expectException(Otomaties\VisualRentingDynamicsSync\Exceptions\ViewNotFoundException::class);
        $this->expectExceptionMessage('View not found: tests/views/incorrect/path/path.php');
        $view = new View('tests/views/');
        $view->render('incorrect/path/path');
    }
    
    public function testCorrectPathDoesNotThrowException(): void
    {
        $view = new View('tests/views/');
        ob_start();
        $view->render('test', ['passedArgument' => 'This is the the passed argument.']);
        $output = ob_get_clean();
        $this->expectNotToPerformAssertions();
    }

    public function testIfViewCanBeRenderedWithoutTrailingSlash(): void
    {
        $view = new View('tests/views');
        ob_start();
        $view->render('test', ['passedArgument' => 'This is the the passed argument.']);
        $output = ob_get_clean();
        $this->assertStringContainsString('This is the view content for testing purposes.', $output);
        $this->assertStringContainsString('This is the the passed argument.', $output);
    }

    public function testCorrectPathRendersCorrectly(): void
    {
        $view = new View('tests/views/');
        ob_start();
        $view->render('/test', ['passedArgument' => 'This is the the passed argument.']);
        $output = ob_get_clean();
        $this->assertStringContainsString('This is the view content for testing purposes.', $output);
        $this->assertStringContainsString('This is the the passed argument.', $output);
    }
}
