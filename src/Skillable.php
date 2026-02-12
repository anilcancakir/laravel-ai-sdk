<?php

namespace Laravel\Ai;

use Laravel\Ai\Skills\SkillMode;
use Laravel\Ai\Skills\SkillRegistry;

trait Skillable
{
    protected ?SkillRegistry $skillRegistry = null;

    /**
     * Get the skills assigned to the agent.
     */
    public function skills(): iterable
    {
        return [];
    }

    /**
     * Get the instructions provided by the agent's skills.
     */
    public function skillInstructions(SkillMode|string|null $mode = null): string
    {
        $this->bootSkillsIfNeeded();

        return $this->skillRegistry->instructions($mode);
    }

    /**
     * Boot the skill registry if it has not already been initialized.
     */
    protected function bootSkillsIfNeeded(): void
    {
        if ($this->skillRegistry !== null) {
            return;
        }

        $this->skillRegistry = app(SkillRegistry::class);

        foreach ($this->skills() as $key => $value) {
            $name = is_int($key) ? $value : $key;
            $mode = is_int($key) ? null : $value;

            $this->skillRegistry->load($name, $mode);
        }
    }
}
