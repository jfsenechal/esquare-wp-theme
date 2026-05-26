<?php

declare(strict_types=1);

namespace Esquare\Theme\Twig;

use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;

final class TwigFactory
{
    public static function create(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2) . '/twig-templates', 'Esquare');

        $env = new Environment($loader, [
            'cache' => false,
            'debug' => \defined('WP_DEBUG') && WP_DEBUG,
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $env->addExtension(new IntlExtension());
        $env->addExtension(new StringExtension());

        return $env;
    }
}
