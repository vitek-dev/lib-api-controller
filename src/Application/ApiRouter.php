<?php

namespace VitekDev\Nette\Application;

use Nette\Application\Routers\RouteList;
use Nette\StaticClass;

final readonly class ApiRouter
{
    use StaticClass;

    public static function createRouter(string $apiVersion = 'v1'): RouteList
    {
        $router = new RouteList();

        $router->addRoute(
            sprintf('api/%s/<module>/<presenter>/<action>[/<id>]', $apiVersion),
        );

        return $router;
    }
}