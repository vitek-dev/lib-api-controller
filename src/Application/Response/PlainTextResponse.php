<?php

namespace VitekDev\Nette\Application\Response;

use Nette\Application\Response;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

class PlainTextResponse implements Response
{
    private mixed $payload;

    public function __construct(mixed $payload)
    {
        $this->payload = $payload;
    }

    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function send(IRequest $httpRequest, IResponse $httpResponse): void
    {
        $httpResponse->setContentType('text/plain', 'utf-8');
        echo $this->payload;
    }
}