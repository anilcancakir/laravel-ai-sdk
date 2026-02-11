<?php

namespace Laravel\Ai\Skills\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Stringable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Skills\SkillRegistry;
use Laravel\Ai\Tools\Request;

class SkillLoader implements Tool
{
    public function __construct(
        protected SkillRegistry $registry
    ) {}

    public function description(): Stringable|string
    {
        return 'Loads a skill by name to make its tools available.';
    }

    public function handle(Request $request): Stringable|string
    {
        $name = $request['skill'];

        $skill = $this->registry->load($name);

        if (! $skill) {
            return sprintf("Skill '%s' not found.", $name);
        }

        return sprintf(
            "Loaded skill '%s'.\n\nInstructions:\n%s",
            $skill->name,
            $skill->instructions
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'skill' => [
                    'type' => 'string',
                    'description' => 'The name of the skill to load',
                ],
            ],
            'required' => ['skill'],
        ];
    }
}
