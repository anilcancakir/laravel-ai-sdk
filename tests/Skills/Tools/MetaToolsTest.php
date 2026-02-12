<?php

namespace Tests\Skills\Tools;

use Illuminate\Support\Facades\File;
use Laravel\Ai\Skills\Skill;
use Laravel\Ai\Skills\SkillDiscovery;
use Laravel\Ai\Skills\SkillRegistry;
use Laravel\Ai\Skills\Tools\ListSkills;
use Laravel\Ai\Skills\Tools\SkillLoader;
use Laravel\Ai\Skills\Tools\SkillReferenceReader;
use Laravel\Ai\Tools\Request;
use Mockery;
use Orchestra\Testbench\TestCase;

class MetaToolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir(__DIR__.'/_fixtures')) {
            mkdir(__DIR__.'/_fixtures', 0777, true);
        }

        file_put_contents(__DIR__.'/_fixtures/test.txt', 'secret content');
        file_put_contents(__DIR__.'/outside.txt', 'forbidden content');
    }

    protected function tearDown(): void
    {
        if (is_dir(__DIR__.'/_fixtures')) {
            File::deleteDirectory(__DIR__.'/_fixtures');
        }
        if (file_exists(__DIR__.'/outside.txt')) {
            unlink(__DIR__.'/outside.txt');
        }

        Mockery::close();
        parent::tearDown();
    }

    public function test_list_skills_returns_all_available_skills()
    {
        $discovery = Mockery::mock(SkillDiscovery::class);
        $discovery->shouldReceive('fresh')->andReturn(collect([
            new Skill(
                name: 'test-skill',
                description: 'A test skill',
                instructions: 'Do things',
                source: 'local'
            ),
            new Skill(
                name: 'another-skill',
                description: 'Another skill',
                instructions: 'Do other things',
                source: 'community'
            ),
        ]));

        $registry = new SkillRegistry($discovery);
        $tool = new ListSkills($registry);

        $result = $tool->handle(new Request([]));

        $this->assertStringContainsString('test-skill', (string) $result);
        $this->assertStringContainsString('A test skill', (string) $result);
        $this->assertStringContainsString('another-skill', (string) $result);
        $this->assertStringContainsString('local', (string) $result);
        $this->assertStringContainsString('community', (string) $result);
    }

    public function test_skill_loader_loads_specific_skill()
    {
        $skill = new Skill(
            name: 'target-skill',
            description: 'Target skill',
            instructions: 'Target instructions',
        );

        $discovery = Mockery::mock(SkillDiscovery::class);
        $discovery->shouldReceive('resolve')
            ->with('target-skill')
            ->andReturn($skill);

        $registry = new SkillRegistry($discovery);
        $tool = new SkillLoader($registry);

        $result = $tool->handle(new Request(['skill' => 'target-skill']));

        $this->assertTrue($registry->isLoaded('target-skill'));
        $this->assertStringContainsString('Target instructions', (string) $result);
    }

    public function test_skill_loader_returns_error_if_skill_not_found()
    {
        $discovery = Mockery::mock(SkillDiscovery::class);
        $discovery->shouldReceive('resolve')
            ->with('missing-skill')
            ->andReturn(null);

        $registry = new SkillRegistry($discovery);
        $tool = new SkillLoader($registry);

        $result = $tool->handle(new Request(['skill' => 'missing-skill']));

        $this->assertStringContainsString('not found', (string) $result);
    }

    public function test_skill_reference_reader_reads_file_within_skill_path()
    {
        $skill = new Skill(
            name: 'fs-skill',
            description: 'FS Skill',
            instructions: '...',
            basePath: __DIR__.'/_fixtures'
        );

        $discovery = Mockery::mock(SkillDiscovery::class);
        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('get')->with('fs-skill')->andReturn($skill);

        $tool = new SkillReferenceReader($registry);

        $result = $tool->handle(new Request([
            'skill' => 'fs-skill',
            'file' => 'test.txt',
        ]));

        $this->assertEquals('secret content', (string) $result);
    }

    public function test_skill_reference_reader_blocks_path_traversal()
    {
        $skill = new Skill(
            name: 'fs-skill',
            description: 'FS Skill',
            instructions: '...',
            basePath: __DIR__.'/_fixtures'
        );

        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('get')->with('fs-skill')->andReturn($skill);

        $tool = new SkillReferenceReader($registry);

        $result = $tool->handle(new Request([
            'skill' => 'fs-skill',
            'file' => '../outside.txt',
        ]));

        $this->assertStringContainsString('Access denied', (string) $result);
    }

    public function test_skill_reference_reader_handles_missing_files()
    {
        $skill = new Skill(
            name: 'fs-skill',
            description: 'FS Skill',
            instructions: '...',
            basePath: __DIR__.'/_fixtures'
        );

        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('get')->with('fs-skill')->andReturn($skill);

        $tool = new SkillReferenceReader($registry);

        $result = $tool->handle(new Request([
            'skill' => 'fs-skill',
            'file' => 'does_not_exist.txt',
        ]));

        $this->assertStringContainsString('not found', (string) $result);
    }
}
