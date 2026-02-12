<?php

namespace Laravel\Ai\Skills;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

class SkillDiscovery
{
    private const string CACHE_KEY = 'ai_sdk_skills';

    public function __construct(
        protected array $paths,
        protected Repository $cache,
        protected int $ttl = 3600
    ) {}

    /**
     * Discover all available skills, using cache when possible.
     */
    public function discover(): Collection
    {
        return $this->cache->remember(
            self::CACHE_KEY,
            $this->ttl,
            fn () => $this->scanLocal()
        );
    }

    /**
     * Invalidate the cache and re-discover all skills.
     */
    public function fresh(): Collection
    {
        $this->cache->forget(self::CACHE_KEY);

        return $this->discover();
    }

    /**
     * Resolve a single skill by its name.
     */
    public function resolve(string $name): ?Skill
    {
        return $this->discover()->first(fn (Skill $skill) => $skill->name === $name);
    }

    /**
     * Scan the local filesystem for skill definitions.
     */
    protected function scanLocal(): Collection
    {
        $skills = collect();

        if (empty($this->paths)) {
            return $skills;
        }

        $existingPaths = array_filter($this->paths, 'is_dir');

        if (empty($existingPaths)) {
            return $skills;
        }

        $finder = new Finder;
        $finder->files()
            ->in($existingPaths)
            ->name('SKILL.md')
            ->depth('== 1');

        foreach ($finder as $file) {
            $skill = SkillParser::parse(
                $file->getContents(),
                'local',
                $file->getPath()
            );

            if ($skill) {
                $skills->push($skill);
            }
        }

        return $skills;
    }
}
