<?php

namespace Laravel\Ai\Gateway\Prism;

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Contracts\Tool;

class OpenAiCompatiblePrismGateway extends PrismGateway
{
    /**
     * {@inheritdoc}
     */
    protected function createPrismTool(Tool $tool): PrismTool
    {
        $toolName = method_exists($tool, 'name')
            ? $tool->name()
            : class_basename($tool);

        return (new PrismTool)
            ->as($toolName)
            ->for((string) $tool->description())
            ->when(
                ! empty($tool->schema(new JsonSchemaTypeFactory)),
                fn ($prismTool) => $prismTool->withParameter(
                    new SanitizedObjectSchema($tool->schema(new JsonSchemaTypeFactory))
                )
            )
            ->using(fn ($arguments) => $this->invokeTool($tool, $arguments))
            ->withoutErrorHandling();
    }
}
