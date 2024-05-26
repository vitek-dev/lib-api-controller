<?php

declare(strict_types=1);

namespace VitekDev\Nette\Application;

use ArgumentCountError;
use DomainException;
use InvalidArgumentException;
use JsonException;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use VitekDev\Nette\Application\Request\RequestBody;
use VitekDev\Nette\Application\Response\StatusResponse;
use VitekDev\Shared\Exceptions\Resources\ResourceNotFound;
use VitekDev\Shared\Exceptions\Security\AuthenticationRequired;
use VitekDev\Shared\Exceptions\Security\AuthorizationInsufficient;
use VitekDev\Shared\Exceptions\Validation\ValidationFailed;

abstract readonly class ApiController implements IPresenter
{
    protected IRequest $httpRequest;
    protected IResponse $httpResponse;
    protected LoggerInterface $logger;

    public function startup(): void
    {
    }

    public function run(Request $request): Response
    {
        $this->startup();

        if ($request->method === IRequest::Options) {
            return StatusResponse::noContent();
        }

        // We expect that the action parameter is always present
        if (!$action = $request->getParameter('action')) {
            $this->logger->critical('Missing <action> parameter in request, do you have correct routing set up?', [
                'request' => $request,
            ]);

            return StatusResponse::internalServerError('Endpoint is unable to route your request');
        }

        // Format method name according to HTTP method, e.g. getIndex
        $methodName = sprintf(
            '%s%s',
            mb_strtolower($request->method),
            ucfirst($action),
        );

        // Verify that corresponding method exists
        $reflection = new ReflectionClass($this);
        if (!$reflection->hasMethod($methodName)) {
            return StatusResponse::methodNotAllowed($request->method);
        }

        // Fill arguments required by the method
        $args = [];
        foreach ($reflection->getMethod($methodName)->getParameters() as $arg) {

            // Request body to object
            $type = $arg->getType();
            if ($type instanceof ReflectionNamedType && is_subclass_of($type->getName(), RequestBody::class)) {
                $postData = $this->httpRequest->getRawBody();
                if (!$postData && !$type->allowsNull()) {
                    return StatusResponse::badRequest('Missing request body');
                }

                if (!$postData && $type->allowsNull()) {
                    $args[$arg->getName()] = null;

                    continue;
                }

                try {
                    $jsonPostData = json_decode($postData, true, flags: JSON_THROW_ON_ERROR);
                    $requestBody = forward_static_call([$type->getName(), 'map'], $jsonPostData);

                    $args[$arg->getName()] = $requestBody;
                } catch (JsonException) {
                    return StatusResponse::badRequest('Malformed request body');
                } catch (ValidationFailed | InvalidArgumentException | DomainException $ex) {
                    return StatusResponse::badRequest($ex->getMessage());
                }

                continue;
            }

            // Route argument
            $argType = $arg->getType()?->getName();
            if (in_array($argType, ['string', 'int', 'float', 'bool'])) {
                $value = (string)$request->getParameter($arg->getName());

                // #1 - if not set, but has default value - use default value
                if ($value === '' && $arg->isDefaultValueAvailable()) {
                    $args[$arg->getName()] = $arg->getDefaultValue();

                    continue;
                }

                // #2 - if not set but is required - error
                if ($value === '' && !$type->allowsNull()) {
                    return StatusResponse::badRequest(
                        sprintf('Missing required parameter %s', $arg->getName()),
                    );
                }

                // #3 - if not set but is optional - use null
                if ($value === '' && $type->allowsNull()) {
                    $args[$arg->getName()] = null;

                    continue;
                }

                // #4 - if set, cast to desired type
                if ($argType === 'bool') {
                    $value = in_array($value, ['1', 'true']);
                } else {
                    settype($value, $argType);
                }

                $args[$arg->getName()] = $value;

                continue;
            }

            // If argument is not supported, code will fail on invokeArgs - ArgumentCountError
        }

        // Execute the method
        try {
            $result = $reflection
                ->getMethod($methodName)
                ->invokeArgs($this, $args);
        } catch (ArgumentCountError $ex) {
            $this->logger->critical('Invalid action parameters! Cannot resolve parameters for method {method} in {class}.', [
                'exception' => $ex,
                'method' => $methodName,
                'class' => $reflection->getName(),
            ]);

            return StatusResponse::internalServerError('Endpoint is unable to handle your request');
        } catch (AuthenticationRequired $ex) {
            return StatusResponse::unauthorized($ex->getMessage());
        } catch (AuthorizationInsufficient $ex) {
            return StatusResponse::forbidden($ex->getMessage());
        } catch (ResourceNotFound $ex) {
            return StatusResponse::notFound($ex->getMessage());
        } catch (DomainException $ex) {
            $this->logger->error($ex->getMessage(), [
                'exception' => $ex,
            ]);

            return StatusResponse::internalServerError($ex->getMessage());
        } catch (Throwable | RuntimeException $ex) {
            $this->logger->error($ex->getMessage(), [
                'exception' => $ex,
            ]);

            return StatusResponse::internalServerError('An unexpected error occurred');
        }

        // Return the result
        if ($result instanceof Response) {
            return $result;
        }

        if (is_scalar($result)) {
            return new TextResponse($result);
        }

        if (is_array($result) || is_object($result)) {
            return new JsonResponse($result);
        }

        return StatusResponse::noContent();
    }

    final public function injectPrimary(IRequest $httpRequest, IResponse $httpResponse, LoggerInterface $logger): void
    {
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
        $this->logger = $logger;
    }
}
