<?php

declare(strict_types=1);

use Otomaties\VisualRentingDynamicsSync\Helpers\View;
use PHPUnit\Framework\TestCase;

final class ViewTest extends TestCase
{
    public function test_incorrect_path_throws_exception(): void
    {
        $this->expectException(Otomaties\VisualRentingDynamicsSync\Exceptions\ViewNotFoundException::class);
        $this->expectExceptionMessage('View not found: tests/views/incorrect/path/path.php');
        $view = new View('tests/views/');
        $view->render('incorrect/path/path');
    }

    public function test_correct_path_does_not_throw_exception(): void
    {
        $view = new View('tests/views/');
        ob_start();
        $view->render('test', ['passedArgument' => 'This is the the passed argument.']);
        $output = ob_get_clean();
        $this->expectNotToPerformAssertions();
    }

    public function test_if_view_can_be_rendered_without_trailing_slash(): void
    {
        $view = new View('tests/views');
        ob_start();
        $view->render('test', ['passedArgument' => 'This is the the passed argument.']);
        $output = ob_get_clean();
        $this->assertStringContainsString('This is the view content for testing purposes.', $output);
        $this->assertStringContainsString('This is the the passed argument.', $output);
    }

    public function test_correct_path_renders_correctly(): void
    {
        $view = new View('tests/views/');
        ob_start();
        $view->render('/test', ['passedArgument' => 'This is the the passed argument.']);
        $output = ob_get_clean();
        $this->assertStringContainsString('This is the view content for testing purposes.', $output);
        $this->assertStringContainsString('This is the the passed argument.', $output);
    }
}
