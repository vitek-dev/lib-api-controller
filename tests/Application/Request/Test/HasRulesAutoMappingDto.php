<?php

namespace VitekDev\Tests\Nette\Application\Request\Test;

use Nette\Schema\Expect;
use VitekDev\Nette\Application\Request\AutoMappingBody;

class HasRulesAutoMappingDto extends AutoMappingBody
{
    public string $name;
    public string $surname;
    public int $age;

    protected static function getCustomRules(): array
    {
        return [
            'age' => Expect::int()->required()->min(25)->max(50),
        ];
    }
}