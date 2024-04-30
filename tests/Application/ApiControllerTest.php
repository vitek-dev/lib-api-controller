<?php

declare(strict_types=1);

namespace VitekDev\Tests\Nette\Application;

use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\Responses\VoidResponse;
use Nette\Http\IResponse;
use PHPUnit\Framework\TestCase;
use VitekDev\Nette\Application\ApiController;
use VitekDev\Nette\Application\Response\StatusResponse;
use VitekDev\Tests\Nette\Application\Test\ApiTestControllers;

/**
 * @covers \VitekDev\Nette\Application\ApiController
 * @covers \VitekDev\Nette\Application\Response\StatusResponse
 */
class ApiControllerTest extends TestCase
{
    use ApiTestControllers;

    //region Generic
    public function testOptionsAlwaysOk(): void
    {
        $response = $this->createPlainController()->run(
            new Request('Api', 'OPTIONS', ['action' => 'randomMethodThatDontEvenExists']),
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S204_NoContent, $response->code);
    }

    public function testMissingActionParameter(): void
    {
        $controller = $this->createPlainController();
        $this->injectMockedServices($controller)['logger']
            ->expects(self::once())
            ->method('critical');

        $response = $controller->run(
            new Request('Api', 'GET', []),
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S500_InternalServerError, $response->code);

        $this->sendResponse($response, '{"status":"Endpoint is unable to route your request"}');
    }

    public function testNotFound(): void
    {
        $response = $this->createPlainController()->run(
            new Request('Api', 'DELETE', ['action' => 'randomMethodThatDontEvenExists']),
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S405_MethodNotAllowed, $response->code);

        $this->sendResponse($response, '{"status":"Endpoint does not support DELETE method"}');
    }
    //endregion

    //region Exception handling
    public function testUnknownParameter(): void
    {
        $controller = $this->createExceptionHandlingController();
        $this->injectMockedServices($controller)['logger']
            ->expects(self::once())
            ->method('critical');

        $response = $controller->run(
            new Request('Api', 'GET', ['action' => 'unknownParameter'])
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S500_InternalServerError, $response->code);

        $this->sendResponse($response, '{"status":"Endpoint is unable to handle your request"}');
    }

    public function testAuthenticationRequired(): void
    {
        $response = $this->createExceptionHandlingController()->run(
            new Request('Api', 'GET', ['action' => 'authenticationRequired'])
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S401_Unauthorized, $response->code);

        $this->sendResponse($response, '{"status":"You need to authenticate first"}');
    }

    public function testInsufficientAuthorization(): void
    {
        $response = $this->createExceptionHandlingController()->run(
            new Request('Api', 'GET', ['action' => 'insufficientAuthorization'])
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S403_Forbidden, $response->code);

        $this->sendResponse($response, '{"status":"You do not have permissions for that"}');
    }

    public function testResourceNotFound(): void
    {
        $response = $this->createExceptionHandlingController()->run(
            new Request('Api', 'GET', ['action' => 'resourceNotFound'])
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S404_NotFound, $response->code);

        $this->sendResponse($response, '{"status":"Resource with identifier uuu-iii-ddd was not found"}');
    }

    public function testDomainException(): void
    {
        $controller = $this->createExceptionHandlingController();
        $this->injectMockedServices($controller)['logger']
            ->expects(self::once())
            ->method('error');

        $response = $controller->run(
            new Request('Api', 'GET', ['action' => 'domainException'])
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S500_InternalServerError, $response->code);

        $this->sendResponse($response, '{"status":"This makes absolutely no sense"}');
    }

    public function testThrowable(): void
    {
        $controller = $this->createExceptionHandlingController();
        $this->injectMockedServices($controller)['logger']
            ->expects(self::once())
            ->method('error');

        $response = $controller->run(
            new Request('Api', 'GET', ['action' => 'throwable'])
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S500_InternalServerError, $response->code);

        $this->sendResponse($response, '{"status":"An unexpected error occurred"}');
    }
    //endregion

    //region Responses
    public function testCustomResponse(): void
    {
        $response = $this->createActionResponseController()->run(
            new Request('Api', 'GET', ['action' => 'customResponse'])
        );

        self::assertInstanceOf(VoidResponse::class, $response);
    }

    public function testStringTextResponse(): void
    {
        $response = $this->createActionResponseController()->run(
            new Request('Api', 'GET', ['action' => 'stringResponse'])
        );

        self::assertInstanceOf(TextResponse::class, $response);

        $this->sendResponse($response, 'Just string response');
    }

    public function testIntTextResponse(): void
    {
        $response = $this->createActionResponseController()->run(
            new Request('Api', 'GET', ['action' => 'intResponse'])
        );

        self::assertInstanceOf(TextResponse::class, $response);

        $this->sendResponse($response, '42');
    }

    public function testArrayResponse(): void
    {
        $response = $this->createActionResponseController()->run(
            new Request('Api', 'GET', ['action' => 'arrayResponse'])
        );

        self::assertInstanceOf(JsonResponse::class, $response);

        $this->sendResponse($response, '{"foo":"bar"}');
    }

    public function testObjectResponse(): void
    {
        $response = $this->createActionResponseController()->run(
            new Request('Api', 'GET', ['action' => 'objectResponse'])
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        $this->sendResponse($response, '{"fofo":"barbar"}');
    }

    public function testNoResponse(): void
    {
        $response = $this->createActionResponseController()->run(
            new Request('Api', 'GET', ['action' => 'noResponse'])
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S204_NoContent, $response->code);
    }
    //endregion

    //region Request body
    public function testMappingRequestBodySuccess(): void
    {
        $response = $this->createRequestBodyController('{"name":"James","surname":"Bond"}')->run(
            new Request('Api', 'POST', ['action' => 'index']),
        );

        self::assertInstanceOf(TextResponse::class, $response);
        self::assertSame('I am Bond, James Bond', $response->getSource());
    }

    public function testMappingRequestBodyInvalid(): void
    {
        $response = $this->createRequestBodyController('{"name":42}')->run(
            new Request('Api', 'POST', ['action' => 'index']),
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S400_BadRequest, $response->code);
    }

    public function testMappingRequiredRequestBodyMissing(): void
    {
        $response = $this->createRequestBodyController(null)->run(
            new Request('Api', 'POST', ['action' => 'index']),
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S400_BadRequest, $response->code);

        $this->sendResponse($response, '{"status":"Missing request body"}');
    }

    public function testMappingOptionalRequestBodyMissing(): void
    {
        $responseHasData = $this->createRequestBodyController('{"name":"James","surname":"Bond"}')->run(
            new Request('Api', 'POST', ['action' => 'optional'])
        );

        $responseNoData = $this->createRequestBodyController(null)->run(
            new Request('Api', 'POST', ['action' => 'optional'])
        );

        self::assertInstanceOf(TextResponse::class, $responseNoData);
        self::assertSame('no data, but that is fine', $responseNoData->getSource());

        self::assertInstanceOf(TextResponse::class, $responseHasData);
        self::assertSame('I am Bond, James Bond', $responseHasData->getSource());
    }

    public function testRequestBodyMalformed(): void
    {
        $response = $this->createRequestBodyController('{"-_f[ads][]')->run(
            new Request('Api', 'POST', ['action' => 'index']),
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S400_BadRequest, $response->code);

        $this->sendResponse($response, '{"status":"Malformed request body"}');
    }
    //endregion

    //region Route parameters
    public function testRouteParametersSuccess(): void
    {
        $response = $this->createRequestParameterController()->run(
            new Request('Api', 'GET', [
                'action' => 'index',
                'name' => 'James',
                'surname' => 'Bond',
                'degree' => 'Ing',
            ]),
        );

        self::assertInstanceOf(TextResponse::class, $response);

        $this->sendResponse($response, 'I am Bond, Ing. James Bond');
    }

    public function testRouteParametersMissingRequired(): void
    {
        $response = $this->createRequestParameterController()->run(
            new Request('Api', 'GET', ['action' => 'index']),
        );

        self::assertInstanceOf(StatusResponse::class, $response);
        self::assertSame(IResponse::S400_BadRequest, $response->code);

        $this->sendResponse($response, '{"status":"Missing required parameter name"}');
    }

    public function testRouteParametersMissingOptionalWithDefaultValue(): void
    {
        $response = $this->createRequestParameterController()->run(
            new Request('Api', 'GET', ['action' => 'index', 'name' => 'James'])
        );

        self::assertInstanceOf(TextResponse::class, $response);

        $this->sendResponse($response, 'I am Doe, Mr. James Doe');
    }

    public function testRouteParametersCasting(): void
    {
        $controller = new readonly class extends ApiController {
            public function getIndex(string $string, int $int, float $float, bool $bool1, bool $bool2, bool $bool3): string {
                $values = [
                    is_string($string),
                    is_int($int),
                    is_float($float),
                    $bool1 === true,
                    $bool2 === true,
                    $bool3 === false,
                ];

                return !in_array(false, $values, true) ? 'GOOD' : 'BAD';
            }
        };

        $responseFail = $controller->run(
            new Request('Api', 'GET', [
                'action' => 'index',
                'string' => 'string',
                'int' => '42',
                'float' => '3.14',
                'bool1' => 'xx',
                'bool2' => 'xx',
                'bool3' => 'xx',
            ])
        );

        $requestSuccess = $controller->run(
            new Request('Api', 'GET', [
                'action' => 'index',
                'string' => 'string',
                'int' => '42',
                'float' => '3.14',
                'bool1' => 'true',
                'bool2' => '1',
                'bool3' => '0',
            ])
        );

        self::assertInstanceOf(TextResponse::class, $responseFail);
        self::assertSame('BAD', $responseFail->getSource());

        self::assertInstanceOf(TextResponse::class, $requestSuccess);
        self::assertSame('GOOD', $requestSuccess->getSource());
    }
    //endregion
}