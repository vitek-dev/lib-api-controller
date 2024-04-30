<?php

namespace VitekDev\Tests\Nette\Application\Response;

use PHPUnit\Framework\TestCase;
use VitekDev\Nette\Application\Response\StatusResponse;

class StatusResponseTest extends TestCase
{
    public function testBadRequest(): void
    {
        $response = StatusResponse::badRequest('Bad request');
        $this->assertSame('Bad request', $response->message);
        $this->assertSame(400, $response->code);
    }

    public function testUnauthorized(): void
    {
        $response = StatusResponse::unauthorized('Unauthorized');
        $this->assertSame('Unauthorized', $response->message);
        $this->assertSame(401, $response->code);
    }

    public function testForbidden(): void
    {
        $response = StatusResponse::forbidden('Forbidden');
        $this->assertSame('Forbidden', $response->message);
        $this->assertSame(403, $response->code);
    }

    public function testNotFound(): void
    {
        $response = StatusResponse::notFound('Not found');
        $this->assertSame('Not found', $response->message);
        $this->assertSame(404, $response->code);
    }

    public function testMethodNotAllowed(): void
    {
        $response = StatusResponse::methodNotAllowed('GET');
        $this->assertSame('Endpoint does not support GET method', $response->message);
        $this->assertSame(405, $response->code);
    }

    public function testInternalServerError(): void
    {
        $response = StatusResponse::internalServerError('Internal server error');
        $this->assertSame('Internal server error', $response->message);
        $this->assertSame(500, $response->code);
    }

    public function testOk(): void
    {
        $response = StatusResponse::ok('OK');
        $this->assertSame('OK', $response->message);
        $this->assertSame(200, $response->code);
    }

    public function testCreated(): void
    {
        $response = StatusResponse::created('Created');
        $this->assertSame('Created', $response->message);
        $this->assertSame(201, $response->code);
    }
}