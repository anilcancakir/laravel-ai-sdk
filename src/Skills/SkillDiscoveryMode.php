<?php

namespace Laravel\Ai\Skills;

enum SkillDiscoveryMode: string
{
    case None = 'none';
    case Lite = 'lite';
    case Full = 'full';
}
