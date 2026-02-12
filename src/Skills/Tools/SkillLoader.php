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

    public function name(): string
    {
        return 'skill_load';
    }

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

        $referenceFiles = $skill->referenceFiles();

        $output = sprintf('<skill name="%s">', $skill->name).PHP_EOL;
        $output .= '<instructions>'.PHP_EOL;
        $output .= $skill->instructions.PHP_EOL;
        $output .= '</instructions>'.PHP_EOL;

        if ($referenceFiles !== []) {
            $fileList = implode(', ', $referenceFiles);

            $output .= '<skill_references>'.PHP_EOL;
            $output .= sprintf('Available files: %s', $fileList).PHP_EOL;
            $output .= sprintf('Use the `skill_read` tool with skill="%s" and file="<filename>" to read these.', $skill->name).PHP_EOL;
            $output .= '</skill_references>'.PHP_EOL;
        }

        $output .= '</skill>';

        return $output;
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
