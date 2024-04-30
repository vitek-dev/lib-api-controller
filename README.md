# API Controller

Simple implementation for `Nette\Application\IPresenter`.

This allows to completely avoid `Nette\Application\UI\Presenter` and focus only on stateless API actions; ApiController avoids e.g.:
- Nette Component model (components hierarchy, forms, custom controls, etc.),
- Signals (handleAction),
- Creating links, canonicalization requests,
- Redirects,
- Templates rendering (you can manually render Latte with Latte Engine but it's not integrated directly inside the Controller)

## Installation


- Add dependency ``composer require vitekdev/nette-api-controller``
- ApiController uses PSR 3 logging, if you want to use Tracy, just register this bridge in your services config: ``Tracy\Bridges\Psr\TracyToPsrLoggerAdapter``
- That's it, you can create your first Controller

## Example

```php
<?php

declare(strict_types=1);

final readonly class HelloController extends \VitekDev\Nette\Application\ApiController
{
    public function __construct(
        private UsersService $usersService,
    ) {
    }
    
    // Controller actions are in httpmethodActionName format

    /**
     * @return UserDto[]
     */
    public function getIndex(): array // List of users
    {
        return $this->usersService->listUsers(); // Automatically translates to JSON
    }
    
    public function postIndex(CreateUser $dto): UserDto // Create new user; automatically maps payload to object
    {
        return $this->usersService->createUser($dto);
    }
    
    public function postSayHello(string $id): string // Say hello to user; automatically maps route parameter to action parameter
    {
        $user = $this->usersService->get($id);
        if (!$user) {
            throw new ResourceNotFound('User not found'); // Automatically returns HTTP 404 Not Found
        }
    
        return sprintf('Hello %s', $user->name); // Automatically sends text response 'Hello John'
    }
}
```

## Features

### Action name with request method

Expected request method is defined directly in action name.

E.g. `public function getFoo(): void` will accept only GET requests, `public function postFoo(): void` will accept only POST requests.

### Auto mapping of route parameters

Route parameters are automatically mapped to action parameters.

E.g. `public function getFoo(int $id): void` will automatically map route parameter `id` to action parameter `$id`.
Parameters itself are handled via `Nette\Routing` (see https://doc.nette.org/en/application/routing#toc-mask-and-parameters).

### Auto mapping of request body JSON to object

All objects extending `VitekDev\Nette\Application\Request\RequestBody` are automatically mapped from request body JSON.

Mapping is done via `::map(array $json): self` method. 
Some exceptions thrown here (`ValidationFailed | InvalidArgumentException | DomainException`) are automatically translated to HTTP 400 Bad Request.

E.g. if we have following DTO class:

```php
class SayHelloDto implements \VitekDev\Nette\Application\Request\RequestBody
{
    public string $name;
    
    public static function map(array $json): self
    {
        $dto = new self();
        $dto->name = $json['name'] ?? throw new InvalidArgumentException('Missing name');
        return $dto;
    }
}
```

We can set up following controller action just as simple as:

```php
public function postHello(SayHelloDto $request): void
{
    // do what you have to do
}
```

#### Validation & fully automated mapping
You can also use e.g. `Nette\Schema` if you want fully automated validation and/or need more complex validation. 

Implementation of `Nette\Schema` and `RequestBody` is already bundled in `VitekDev\Nette\Application\Request\AutoMappedRequestBody`.

If you need to add some additional validation, you can override method `::getCustomRules(): array` in implementing class.

```php
class SayHelloDto extends \VitekDev\Nette\Application\Request\AutoMappedRequestBody
{
    public string $name;
    
    public int $age;
    
    public static function getCustomRules(): array
    {
        return [
            'age' => \Nette\Schema\Expect::int()->min(0)->max(90),
        ];
    }
}
```

### Action result = Controller response

The ApiController automatically sends response with data returned from action.

```php
public function getCustomResponse(): \Nette\Application\Response
{
    return new Nette\Application\Responses\VoidResponse(); // you can manually send any compatible Response
}

public function getText(): string
{
    return 'hello world'; // will send HTTP 200 response with 'hello world' text
}

public function getJson(): array
{
    return ['foo' => 'bar']; // will send HTTP 200 response with '{"foo":"bar"}' JSON
}

public function getDto(): SayHelloDto
{
    $dto = new SayHelloDto();
    $dto->name = 'John';
    return $dto; // will send HTTP 200 response with '{"name":"John"}' JSON
}

public function getJustNothing(): void
{
    // will send HTTP 204 No Content
}
```

### Automatic handling specific Exceptions

Listed exceptions are automatically translated to `VitekDev\Nette\Application\Response\StatusResponse`.

- `DomainException` => HTTP 500 Internal Server Error (**with exception message**)
- `VitekDev\Shared\Exceptions\AuthenticationRequired` => HTTP 401 Unauthorized (**with exception message**)
- `VitekDev\Shared\Exceptions\AuthorizationInsufficient` => HTTP 403 Forbidden (**with exception message**)
- `VitekDev\Shared\Exceptions\ResourceNotFound` => HTTP 404 Not Found (**with exception message**)

All other errors, especially `RuntimeException`, are translated to HTTP 500 Internal Server Error **<u>without</u> exception message**.

## Recommended Router

You can use Route as simple as: `api/v1/<module>/<presenter>/<action>[/<id>]` that is already bundled and ready to use: `VitekDev\Nette\Application\ApiRouter`.


## Side notes

### Stateless

The ApiController is readonly so all child classes must be readonly.