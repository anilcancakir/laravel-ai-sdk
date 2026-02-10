<?php

namespace Tests\Unit\Gateway;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Gateway\Prism\OpenAiCompatiblePrismGateway;
use Laravel\Ai\Gateway\Prism\SanitizedObjectSchema;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\TestCase;

class OpenAiCompatiblePrismGatewayTest extends TestCase
{
    public function test_it_strips_unsupported_fields_from_schema()
    {
        $schema = [
            'name' => 'root_schema',
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'field_one' => [
                    'type' => 'string',
                    'name' => 'field_one_name',
                ],
                'nested' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'inner' => [
                            'type' => 'integer',
                            'name' => 'inner_name',
                        ],
                    ],
                ],
            ],
        ];

        $reflection = new \ReflectionClass(SanitizedObjectSchema::class);
        $method = $reflection->getMethod('sanitize');
        $method->setAccessible(true);

        $sanitized = $method->invoke(null, $schema);

        $this->assertArrayNotHasKey('name', $sanitized);
        $this->assertArrayNotHasKey('additionalProperties', $sanitized);

        $this->assertArrayHasKey('properties', $sanitized);
        $this->assertArrayNotHasKey('name', $sanitized['properties']['field_one']);

        $this->assertArrayNotHasKey('additionalProperties', $sanitized['properties']['nested']);
        $this->assertArrayNotHasKey('name', $sanitized['properties']['nested']['properties']['inner']);
    }

    public function test_it_sanitizes_array_items()
    {
        $schema = [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'name' => 'item_name',
                'additionalProperties' => false,
                'properties' => [
                    'id' => ['type' => 'integer', 'name' => 'id_name'],
                ],
            ],
        ];

        $reflection = new \ReflectionClass(SanitizedObjectSchema::class);
        $method = $reflection->getMethod('sanitize');
        $method->setAccessible(true);

        $sanitized = $method->invoke(null, $schema);

        $this->assertArrayHasKey('items', $sanitized);
        $this->assertArrayNotHasKey('name', $sanitized['items']);
        $this->assertArrayNotHasKey('additionalProperties', $sanitized['items']);
        $this->assertArrayNotHasKey('name', $sanitized['items']['properties']['id']);
    }

    public function test_it_sanitizes_any_of_structures()
    {
        $schema = [
            'anyOf' => [
                [
                    'type' => 'object',
                    'name' => 'variant_1',
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'name' => 'variant_2',
                    'additionalProperties' => false,
                ],
            ],
        ];

        $reflection = new \ReflectionClass(SanitizedObjectSchema::class);
        $method = $reflection->getMethod('sanitize');
        $method->setAccessible(true);

        $sanitized = $method->invoke(null, $schema);

        $this->assertCount(2, $sanitized['anyOf']);
        $this->assertArrayNotHasKey('name', $sanitized['anyOf'][0]);
        $this->assertArrayNotHasKey('additionalProperties', $sanitized['anyOf'][0]);
        $this->assertArrayNotHasKey('name', $sanitized['anyOf'][1]);
        $this->assertArrayNotHasKey('additionalProperties', $sanitized['anyOf'][1]);
    }

    public function test_gateway_uses_sanitized_schema_for_tools()
    {
        $tool = new class implements \Laravel\Ai\Contracts\Tool
        {
            public function name(): string
            {
                return 'test_tool';
            }

            public function description(): \Stringable|string
            {
                return 'A test tool';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'name' => 'should_be_removed',
                    'type' => 'object',
                    'properties' => ['foo' => ['type' => 'string']],
                ];
            }

            public function handle(Request $request): \Stringable|string
            {
                return '';
            }
        };

        $gateway = new class extends OpenAiCompatiblePrismGateway
        {
            public function __construct() {}

            public function exposeCreatePrismTool($tool)
            {
                return $this->createPrismTool($tool);
            }

            protected function invokeTool(\Laravel\Ai\Contracts\Tool $tool, array $arguments): string
            {
                return '';
            }
        };

        $prismTool = $gateway->exposeCreatePrismTool($tool);

        $ref = new \ReflectionClass($prismTool);

        $params = $ref->getProperties();
        $paramProp = null;

        foreach ($params as $p) {
            if (str_contains(strtolower($p->getName()), 'parameter')) {
                $p->setAccessible(true);
                $paramProp = $p;

                break;
            }
        }

        $this->assertNotNull($paramProp, 'Could not find parameter property on PrismTool.');

        $parameters = $paramProp->getValue($prismTool);

        $this->assertIsArray($parameters);
        $this->assertNotEmpty($parameters);

        $schemaObj = reset($parameters);

        $this->assertInstanceOf(SanitizedObjectSchema::class, $schemaObj);
    }
}
