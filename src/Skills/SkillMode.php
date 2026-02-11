<?php

namespace Laravel\Ai\Skills;

enum SkillMode: string
{
    case None = 'none';
    case Lite = 'lite';
    case Full = 'full';

    public static function fromValue(string|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::from(strtolower($value));
    }
}
