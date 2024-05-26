<?php

declare(strict_types=1);

namespace VitekDev\Nette\Application\Request;

interface RequestBody
{
    public static function map(array $input): static;
}