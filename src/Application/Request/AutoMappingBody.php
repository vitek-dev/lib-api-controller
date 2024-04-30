<?php

namespace VitekDev\Nette\Application\Request;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use VitekDev\Shared\Exceptions\ValidationFailed;

abstract class AutoMappingBody implements RequestBody
{
    public static function map(array $input): static
    {
        try {
            return (new Processor())->process(
                Expect::from(new static(), static::getCustomRules()),
                $input,
            );
        } catch (ValidationException $ex) {
            throw new ValidationFailed($ex->getMessages());
        }
    }

    protected static function getCustomRules(): array
    {
        return [];
    }
}