<?php

namespace App\Twig;

use App\Service\MenuConfigurationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuVisibilityExtension extends AbstractExtension
{
    public function __construct(
        private readonly MenuConfigurationService $menuConfigurationService,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('menu_visible', [$this, 'isMenuVisible']),
            new TwigFunction('menu_group_visible', [$this, 'isMenuGroupVisible']),
        ];
    }

    public function isMenuVisible(string $code): bool
    {
        return $this->menuConfigurationService->isVisible($code);
    }

    public function isMenuGroupVisible(string $groupCode): bool
    {
        return $this->menuConfigurationService->isGroupVisible($groupCode);
    }
}
