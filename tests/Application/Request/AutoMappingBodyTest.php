<?php

declare(strict_types=1);

namespace VitekDev\Tests\Nette\Application\Request;

use PHPUnit\Framework\TestCase;
use VitekDev\Shared\Exceptions\ValidationFailed;
use VitekDev\Tests\Nette\Application\Request\Test\HasRulesAutoMappingDto;
use VitekDev\Tests\Nette\Application\Request\Test\NoRulesAutoMappingDto;

class AutoMappingBodyTest extends TestCase
{
    public function testSuccess(): void
    {
        $input = [
            'name' => 'John',
            'surname' => 'Doe',
            'age' => 30,
        ];

        $expected = new HasRulesAutoMappingDto();
        $expected->name = $input['name'];
        $expected->surname = $input['surname'];
        $expected->age = $input['age'];

        $this->assertEquals(
            $expected,
            HasRulesAutoMappingDto::map($input),
        );
    }

    public function testValidationFailed(): void
    {
        $input = [
            'name' => 'John',
            'surname' => 'Doe',
            'age' => 265,
        ];

        $success = NoRulesAutoMappingDto::map($input);
        self::assertInstanceOf(NoRulesAutoMappingDto::class, $success);

        $this->expectException(ValidationFailed::class);
        HasRulesAutoMappingDto::map($input);
    }
}