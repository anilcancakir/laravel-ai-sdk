<?php

namespace Laravel\Ai\Skills;

use Illuminate\Support\Str;

final readonly class Skill
{
    /**
     * @param  array<string>  $triggers
     * @param  array<string, mixed>  $constraints
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $instructions,
        public array $triggers = [],
        public ?string $version = null,
        public array $constraints = [],
        public string $source = 'local',
        public ?string $basePath = null,
    ) {}

    /**
     * Get the URL-friendly slug for the skill name.
     */
    public function slug(): string
    {
        return Str::slug($this->name);
    }

    /**
     * Determine if the given input matches any of the skill's triggers.
     */
    public function matchesTrigger(string $input): bool
    {
        return Str::contains(
            Str::lower($input),
            array_map(fn ($t) => Str::lower($t), $this->triggers)
        );
    }
}
