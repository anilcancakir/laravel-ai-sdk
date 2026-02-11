<?php

namespace Laravel\Ai\Skills;

use Illuminate\Support\Str;

final readonly class Skill
{
    /**
     * @param  array<string, mixed>  $tools
     * @param  array<string>  $triggers
     * @param  array<string, mixed>  $constraints
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $instructions,
        public array $tools = [],
        public array $triggers = [],
        public ?string $version = null,
        public array $constraints = [],
        public string $source = 'local',
        public ?string $basePath = null,
    ) {}

    public function slug(): string
    {
        return Str::slug($this->name);
    }

    public function hasTools(): bool
    {
        return count($this->tools) > 0;
    }

    public function matchesTrigger(string $input): bool
    {
        return Str::contains(
            Str::lower($input),
            array_map(fn ($t) => Str::lower($t), $this->triggers)
        );
    }
}
