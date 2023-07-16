<?php

/** Declare strict types */

declare(strict_types=1);

namespace ConditionTests;

use FormToArray\Parser;

/** PHPUnit namespace */
use PHPUnit\Framework\TestCase;

/**
 * VariablesTest class
 */
final class InputsTest extends TestCase
{
    /**
     * Simple input
     * @return void
     */
    public function testSimpleInput(): void
    {
        $parser = new Parser(file_get_contents(__DIR__ . '/simple-text.html'));

        $this->assertEquals(
            '{"form":{"action":"\/","method":"GET"},"input_types":{"input":{"text":[{"type":"text","name":"test","id":"test","placeholder":"test","label":"Test Label","input_type":"input"}]}}}',
            $form_json = $parser->toJson(pretty_print: false)
        );
    }
}
