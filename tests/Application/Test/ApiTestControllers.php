<?php

declare(strict_types=1);

namespace VitekDev\Tests\Nette\Application\Test;

use DomainException;
use Exception;
use Nette\Application\IPresenter;
use Nette\Application\Response;
use Nette\Application\Responses\VoidResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use VitekDev\Nette\Application\ApiController;
use VitekDev\Shared\Exceptions\Resources\ResourceNotFound;
use VitekDev\Shared\Exceptions\Security\AuthenticationRequired;
use VitekDev\Shared\Exceptions\Security\AuthorizationInsufficient;
use VitekDev\Tests\Nette\Application\ApiControllerTest;

trait ApiTestControllers
{
    protected function createExceptionHandlingController(): ApiController
    {
        return new readonly class extends ApiController {
            public function getUnknownParameter(ApiControllerTest $unknownParameter): void
            {
            }

            public function getAuthenticationRequired(): void
            {
                throw new AuthenticationRequired('You need to authenticate first');
            }

            public function getInsufficientAuthorization(): void
            {
                throw new AuthorizationInsufficient('You do not have permissions for that');
            }

            public function getResourceNotFound(): void
            {
                throw new ResourceNotFound('uuu-iii-ddd');
            }

            public function getDomainException(): void
            {
                throw new DomainException('This makes absolutely no sense');
            }

            public function getThrowable(): void
            {
                throw new Exception('The universe has collapsed');
            }
        };
    }

    protected function createRequestBodyController(?string $rawBody): ApiController
    {
        $controller = new readonly class extends ApiController {
            public function postIndex(RequestBodyDto $dto): string
            {
                return sprintf('I am %s, %s %s', $dto->surname, $dto->name, $dto->surname);
            }

            public function postOptional(?RequestBodyDto $dto): string
            {
                if ($dto) {
                    return $this->postIndex($dto);
                }

                return 'no data, but that is fine';
            }
        };

        $this->injectMockedServices($controller)['httpRequest']->method('getRawBody')->willReturn($rawBody);

        return $controller;
    }

    protected function createRequestParameterController(): ApiController
    {
        return new readonly class extends ApiController {
            public function getIndex(string $name, ?string $surname, ?string $degree = 'Mr'): string {
                $surname ??= 'Doe';

                return sprintf('I am %s, %s. %s %s', $surname, $degree, $name, $surname);
            }
        };
    }

    protected function createActionResponseController(): ApiController
    {
        return new readonly class extends ApiController {
            public function getCustomResponse(): Response
            {
                return new VoidResponse();
            }

            public function getStringResponse(): string
            {
                return 'Just string response';
            }

            public function getIntResponse(): int
            {
                return 42;
            }

            public function getArrayResponse(): array
            {
                return ['foo' => 'bar'];
            }

            public function getObjectResponse(): object
            {
                return (object) ['fofo' => 'barbar'];
            }

            public function getNoResponse(): void
            {
                // nothing to do
            }
        };
    }

    protected function createPlainController(): ApiController
    {
        return new readonly class extends ApiController {
            public function getIndex(): void {
                // nothing to do
            }
        };
    }

    protected function sendResponse(Response $response, string $expectedOutput): void
    {
        $this->expectOutputString($expectedOutput);

        $response->send(
            $this->createMock(IRequest::class),
            $this->createMock(IResponse::class),
        );
    }

    /**
     * @return array{httpRequest: MockObject, httpResponse: MockObject, logger: MockObject}
     */
    protected function injectMockedServices(IPresenter $controller): array
    {
        $httpResponse = $this->createMock(IResponse::class);
        $httpRequest = $this->createMock(IRequest::class);
        $logger = $this->createMock(LoggerInterface::class);

        $controller->injectPrimary($httpRequest, $httpResponse, $logger);

        return [
            'httpRequest' => $httpRequest,
            'httpResponse' => $httpResponse,
            'logger' => $logger,
        ];
    }
}