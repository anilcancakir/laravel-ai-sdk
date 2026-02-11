<?php

namespace Laravel\Ai\Skills;

class SkillRegistry
{
    /**
     * @var array<string, Skill>
     */
    protected array $skills = [];

    public function __construct(
        protected mixed $discovery
    ) {}

    public function load(string $name): ?Skill
    {
        if ($skill = $this->discovery->resolve($name)) {
            $this->skills[$name] = $skill;
        }

        return $skill;
    }

    public function isLoaded(string $name): bool
    {
        return isset($this->skills[$name]);
    }

    public function get(string $name): ?Skill
    {
        return $this->skills[$name] ?? null;
    }

    /**
     * @return array<string, Skill>
     */
    public function getLoaded(): array
    {
        return $this->skills;
    }

    public function discover(): \Illuminate\Support\Collection
    {
        return $this->discovery->fresh();
    }

    public function instructions(?string $mode): string
    {
        if ($mode === 'full') {
            return collect($this->skills)
                ->map(fn (Skill $skill) => sprintf(
                    '<skill name="%s">%s%s%s</skill>',
                    $skill->name,
                    PHP_EOL,
                    $skill->instructions,
                    PHP_EOL
                ))
                ->implode(PHP_EOL);
        }

        if ($mode === 'lite') {
            return collect($this->skills)
                ->map(fn (Skill $skill) => sprintf(
                    '<skill name="%s" description="%s" />',
                    $skill->name,
                    $skill->description
                ))
                ->implode(PHP_EOL);
        }

        return '';
    }
}
