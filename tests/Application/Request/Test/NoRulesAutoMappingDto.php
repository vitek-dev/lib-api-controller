<?php

declare(strict_types=1);

namespace VitekDev\Tests\Nette\Application\Request\Test;

use Nette\Schema\Expect;
use VitekDev\Nette\Application\Request\AutoMappingBody;

class NoRulesAutoMappingDto extends AutoMappingBody
{
    public string $name;
    public string $surname;
    public int $age;
}