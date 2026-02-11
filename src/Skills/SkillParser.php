<?php

namespace Laravel\Ai\Skills;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

class SkillParser
{
    public static function parse(string $content, string $source = 'local', ?string $basePath = null): ?Skill
    {
        if (! str_starts_with($content, '---')) {
            Log::warning('Skill content must start with YAML frontmatter.');

            return null;
        }

        $parts = explode('---', $content, 3);

        if (count($parts) < 3) {
            Log::warning('Skill content must contain YAML frontmatter and Markdown body.');

            return null;
        }

        try {
            $frontmatter = Yaml::parse($parts[1]);
        } catch (\Exception $e) {
            Log::warning('Failed to parse skill frontmatter: '.$e->getMessage());

            return null;
        }

        $body = trim($parts[2]);

        if (! isset($frontmatter['name']) || ! isset($frontmatter['description'])) {
            Log::warning('Skill frontmatter must contain "name" and "description".');

            return null;
        }

        return new Skill(
            name: $frontmatter['name'],
            description: $frontmatter['description'],
            instructions: $body,
            tools: $frontmatter['tools'] ?? [],
            triggers: $frontmatter['triggers'] ?? [],
            version: $frontmatter['version'] ?? null,
            constraints: $frontmatter['constraints'] ?? [],
            source: $source,
            basePath: $basePath,
        );
    }
}
