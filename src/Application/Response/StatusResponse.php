<?php

declare(strict_types=1);

namespace VitekDev\Nette\Application\Response;

use Nette;
use Nette\Application\Response;
use Override;

final readonly class StatusResponse implements Response
{
    public function __construct(
        public ?string $message,
        public int $code,
    ) {
    }

    public static function badRequest(string $message): self
    {
        return new self($message, Nette\Http\IResponse::S400_BadRequest);
    }

    public static function unauthorized(string $message): self
    {
        return new self($message, Nette\Http\IResponse::S401_Unauthorized);
    }

    public static function forbidden(string $message): self
    {
        return new self($message, Nette\Http\IResponse::S403_Forbidden);
    }

    public static function notFound(string $message): self
    {
        return new self($message, Nette\Http\IResponse::S404_NotFound);
    }

    public static function methodNotAllowed(string $method): self
    {
        return new self(
            sprintf('Endpoint does not support %s method', strtoupper($method)),
            Nette\Http\IResponse::S405_MethodNotAllowed,
        );
    }

    public static function internalServerError(string $message): self
    {
        return new self($message, Nette\Http\IResponse::S500_InternalServerError);
    }

    public static function ok(string $message = 'OK'): self
    {
        return new self($message, Nette\Http\IResponse::S200_OK);
    }

    public static function created(string $message): self
    {
        return new self($message, Nette\Http\IResponse::S201_Created);
    }

    public static function noContent(): self
    {
        return new self(null, Nette\Http\IResponse::S204_NoContent);
    }

    #[Override] public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $httpResponse->setCode($this->code);
        $httpResponse->setContentType('application/json', 'utf-8');

        if ($this->message) {
            echo json_encode([
                'status' => $this->message,
            ]);
        }
    }
}