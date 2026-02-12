<?php

namespace Laravel\Ai\Skills\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Stringable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Skills\SkillRegistry;
use Laravel\Ai\Tools\Request;

class SkillReferenceReader implements Tool
{
    /**
     * Create a new tool instance.
     */
    public function __construct(
        protected SkillRegistry $registry
    ) {}

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'skill_read';
    }

    /**
     * Get the description of the tool.
     */
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

        // Security check: block path traversal patterns
        if (str_contains($fileName, '..') || str_starts_with($fileName, '/')) {
            return 'Access denied: Cannot read outside skill directory.';
        }

        $filePath = $skill->basePath.'/'.$fileName;

        if (! file_exists($filePath)) {
            return sprintf("File '%s' not found in skill directory.", $fileName);
        }

        $path = realpath($filePath);
        $basePath = realpath($skill->basePath);

        // Double-check resolved path stays within skill directory
        if (! $path || ! $basePath || ! str_starts_with($path, $basePath)) {
            return 'Access denied: Cannot read outside skill directory.';
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return sprintf("Failed to read file '%s'.", $fileName);
        }

        return $content;
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
