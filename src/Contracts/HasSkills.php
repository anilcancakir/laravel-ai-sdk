<?php

namespace Laravel\Ai\Contracts;

use Laravel\Ai\Skills\SkillDiscoveryMode;

interface HasSkills
{
    public function skills(): iterable;

    public function skillDiscoveryMode(): SkillDiscoveryMode;
}
