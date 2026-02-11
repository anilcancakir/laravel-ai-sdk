<?php

namespace Laravel\Ai;

use Laravel\Ai\Skills\SkillMode;
use Laravel\Ai\Skills\SkillRegistry;

trait Skillable
{
    protected ?SkillRegistry $skillRegistry = null;

    public function skills(): iterable
    {
        return [];
    }

    public function skillTools(): array
    {
        $this->bootSkillsIfNeeded();

        return $this->skillRegistry->tools();
    }

    public function skillInstructions(?SkillMode $mode = null): string
    {
        $this->bootSkillsIfNeeded();

        return $this->skillRegistry->instructions($mode?->value);
    }

    protected function bootSkillsIfNeeded(): void
    {
        if ($this->skillRegistry !== null) {
            return;
        }

        $this->skillRegistry = app(SkillRegistry::class);

        foreach ($this->skills() as $key => $value) {
            $name = is_int($key) ? $value : $key;

            $this->skillRegistry->load($name);
        }
    }
}
