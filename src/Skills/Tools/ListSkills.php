<?php

namespace Laravel\Ai\Skills\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Stringable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Skills\SkillRegistry;
use Laravel\Ai\Tools\Request;

class ListSkills implements Tool
{
    private ?string $memoizedDescription = null;

    public function __construct(
        protected SkillRegistry $registry
    ) {}

    public function description(): Stringable|string
    {
        if ($this->memoizedDescription !== null) {
            return $this->memoizedDescription;
        }

        $skills = $this->registry->discover();

        $xml = $skills->map(fn ($skill) => sprintf(
            '  <skill name="%s" description="%s" />',
            $skill->name,
            $skill->description,
        ))->implode("\n");

        $block = $xml !== ''
            ? "<available_skills>\n{$xml}\n</available_skills>"
            : "<available_skills>\n</available_skills>";

        $this->memoizedDescription = "Lists all available skills that can be loaded.\n\n{$block}";

        return $this->memoizedDescription;
    }

    public function handle(Request $request): Stringable|string
    {
        $skills = $this->registry->discover();

        $header = '| Name | Description | Source | Status |';
        $separator = '|---|---|---|---|';

        $rows = $skills->map(fn ($skill) => sprintf(
            '| %s | %s | %s | %s |',
            $skill->name,
            $skill->description,
            $skill->source,
            $this->registry->isLoaded($skill->name) ? 'Loaded' : 'Available',
        ))->implode("\n");

        return "{$header}\n{$separator}\n{$rows}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }
}
