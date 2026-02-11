<?php

namespace Laravel\Ai\Skills\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Stringable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Skills\SkillRegistry;
use Laravel\Ai\Tools\Request;

class ListSkills implements Tool
{
    public function __construct(
        protected SkillRegistry $registry
    ) {}

    public function description(): Stringable|string
    {
        return 'Lists all available skills that can be loaded.';
    }

    public function handle(Request $request): Stringable|string
    {
        $skills = $this->registry->discover();

        return $skills->map(function ($skill) {
            return sprintf(
                '- %s: %s (Source: %s)',
                $skill->name,
                $skill->description,
                $skill->source
            );
        })->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }
}
