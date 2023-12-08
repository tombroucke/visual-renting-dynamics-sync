<?php 

namespace Otomaties\VisualRentingDynamicsSync\Helpers;

use Otomaties\VisualRentingDynamicsSync\Exceptions\ViewNotFoundException;

class View
{
    private $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/') . '/';
    }

    public function render(string $view, array $context = []) : void
    {
        $view = $this->path . ltrim($view, '/') . '.php';
        if (!file_exists($view)) {
            throw new ViewNotFoundException('View not found: ' . $view);
        }

        extract($context, EXTR_SKIP);
        include $view;
    }
}
