<?php

namespace Tests\Skills;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Skills\Skill;
use Laravel\Ai\Skills\SkillDiscovery;
use Tests\TestCase;

class SkillDiscoveryTest extends TestCase
{
    protected string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempPath = __DIR__.'/../temp_skills';
        File::makeDirectory($this->tempPath, 0755, true, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempPath);
        parent::tearDown();
    }

    public function test_it_discovers_skills_in_given_paths()
    {
        $skillDir = $this->tempPath.'/test-skill';
        File::makeDirectory($skillDir);
        File::put($skillDir.'/SKILL.md', <<<'MD'
---
name: Test Skill
description: A test skill description
---
Test instructions
MD
        );

        $discovery = new SkillDiscovery([$this->tempPath], Cache::store('array'));
        $skills = $discovery->discover();

        $this->assertCount(1, $skills);
        $this->assertInstanceOf(Skill::class, $skills->first());
        $this->assertEquals('Test Skill', $skills->first()->name);
    }

    public function test_it_caches_discovered_skills()
    {
        $skillDir = $this->tempPath.'/test-skill';
        File::makeDirectory($skillDir);
        File::put($skillDir.'/SKILL.md', '---
name: Cached Skill
description: Description
---
Body');

        $cache = Cache::store('array');
        $discovery = new SkillDiscovery([$this->tempPath], $cache);

        $discovery->discover();
        $this->assertTrue($cache->has('ai_sdk_skills'));

        File::deleteDirectory($skillDir);

        $skills = $discovery->discover();

        $this->assertCount(1, $skills);
        $this->assertEquals('Cached Skill', $skills->first()->name);
    }

    public function test_fresh_bypasses_cache()
    {
        $skillDir = $this->tempPath.'/test-skill';
        File::makeDirectory($skillDir);
        File::put($skillDir.'/SKILL.md', '---
name: Initial Skill
description: Description
---
Body');

        $cache = Cache::store('array');
        $discovery = new SkillDiscovery([$this->tempPath], $cache);

        $discovery->discover();

        File::put($skillDir.'/SKILL.md', '---
name: Updated Skill
description: Description
---
Body');

        $this->assertEquals('Initial Skill', $discovery->discover()->first()->name);

        $this->assertEquals('Updated Skill', $discovery->fresh()->first()->name);
    }

    public function test_resolve_finds_skill_by_name()
    {
        $skillDir = $this->tempPath.'/test-skill';
        File::makeDirectory($skillDir);
        File::put($skillDir.'/SKILL.md', '---
name: Target Skill
description: Description
---
Body');

        $discovery = new SkillDiscovery([$this->tempPath], Cache::store('array'));

        $skill = $discovery->resolve('Target Skill');
        $this->assertNotNull($skill);
        $this->assertEquals('Target Skill', $skill->name);

        $this->assertNull($discovery->resolve('Non Existent'));
    }

    public function test_returns_empty_collection_for_empty_paths()
    {
        $discovery = new SkillDiscovery([], Cache::store('array'));

        $this->assertTrue($discovery->discover()->isEmpty());
    }

    public function test_it_accepts_custom_cache_ttl()
    {
        $skillDir = $this->tempPath.'/ttl-skill';
        File::makeDirectory($skillDir);
        File::put($skillDir.'/SKILL.md', '---
name: TTL Skill
description: Description
---
Body');

        $cache = Cache::store('array');
        $discovery = new SkillDiscovery([$this->tempPath], $cache, 60);

        $skills = $discovery->discover();

        $this->assertCount(1, $skills);
        $this->assertTrue($cache->has('ai_sdk_skills'));
    }
}
