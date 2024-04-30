<?php

namespace VitekDev\Tests\Nette\Application\Test;

use VitekDev\Nette\Application\Request\AutoMappingBody;

class RequestBodyDto extends AutoMappingBody
{
    public string $name;
    public ?string $surname;
}