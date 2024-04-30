<?php

namespace VitekDev\Tests\Nette\Application;

use PHPUnit\Framework\TestCase;
use VitekDev\Nette\Application\ApiRouter;

class ApiRouterTest extends TestCase
{
    public function testCreateRouter(): void
    {
        $router = ApiRouter::createRouter('x42');

        $this->assertSame(
            'api/x42/<module>/<presenter>/<action>[/<id>]',
            $router->getRouters()[0]->getMask(),
        );
    }
}