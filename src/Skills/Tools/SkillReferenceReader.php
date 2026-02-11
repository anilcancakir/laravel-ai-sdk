<?php

namespace Laravel\Ai\Skills\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Stringable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Skills\SkillRegistry;
use Laravel\Ai\Tools\Request;

class SkillReferenceReader implements Tool
{
    public function __construct(
        protected SkillRegistry $registry
    ) {}

    public function description(): Stringable|string
    {
        return 'Reads a file from a skill\'s directory.';
    }

    public function handle(Request $request): Stringable|string
    {
        $skillName = $request['skill'];
        $fileName = $request['file'];

        $skill = $this->registry->get($skillName);

        if (! $skill) {
            return sprintf("Skill '%s' not loaded or not found.", $skillName);
        }

        if (! $skill->basePath) {
            return sprintf("Skill '%s' does not have a base path.", $skillName);
        }

        if (! file_exists($skill->basePath.'/'.$fileName)) {
            return sprintf("File '%s' not found in skill directory.", $fileName);
        }

        $path = realpath($skill->basePath.'/'.$fileName);

        // Security check: Ensure the resolved path starts with the skill's base path
        if (! $path || ! str_starts_with($path, realpath($skill->basePath))) {
            return 'Access denied: Cannot read outside skill directory.';
        }

        return file_get_contents($path);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'skill' => [
                    'type' => 'string',
                    'description' => 'The name of the skill',
                ],
                'file' => [
                    'type' => 'string',
                    'description' => 'The relative path to the file within the skill directory',
                ],
            ],
            'required' => ['skill', 'file'],
        ];
    }
}
