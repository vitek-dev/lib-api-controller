<?php

namespace VitekDev\Nette\Application\Request;

interface RequestBody
{
    public static function map(array $input): static;
}