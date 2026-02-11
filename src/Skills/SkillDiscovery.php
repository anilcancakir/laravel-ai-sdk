<?php

namespace Laravel\Ai\Skills;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

class SkillDiscovery
{
    private const string CACHE_KEY = 'ai_sdk_skills';

    private const int CACHE_TTL = 3600;

    public function __construct(
        protected array $paths,
        protected Repository $cache
    ) {}

    public function discover(): Collection
    {
        return $this->cache->remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn () => $this->scanLocal()
        );
    }

    public function fresh(): Collection
    {
        $this->cache->forget(self::CACHE_KEY);

        return $this->discover();
    }

    public function resolve(string $name): ?Skill
    {
        return $this->discover()->first(fn (Skill $skill) => $skill->name === $name);
    }

    protected function scanLocal(): Collection
    {
        $skills = collect();

        if (empty($this->paths)) {
            return $skills;
        }

        $finder = new Finder;
        $finder->files()
            ->in($this->paths)
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
