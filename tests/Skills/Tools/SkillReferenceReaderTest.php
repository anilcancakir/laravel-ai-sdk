<?php

namespace Tests\Skills\Tools;

use Laravel\Ai\Skills\Skill;
use Laravel\Ai\Skills\SkillRegistry;
use Laravel\Ai\Skills\Tools\SkillReferenceReader;
use Laravel\Ai\Tools\Request;
use Mockery;
use Tests\TestCase;

class SkillReferenceReaderTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPath = sys_get_temp_dir().'/skill-ref-reader-test-'.uniqid();
        mkdir($this->tempPath, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempPath)) {
            array_map('unlink', glob($this->tempPath.'/*'));
            rmdir($this->tempPath);
        }

        Mockery::close();
        parent::tearDown();
    }

    public function test_reads_valid_file_within_skill_directory(): void
    {
        file_put_contents($this->tempPath.'/guide.md', '# Guide Content');

        $skill = new Skill(
            name: 'my-skill',
            description: 'Test skill',
            instructions: 'Instructions',
            basePath: $this->tempPath,
        );

        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('get')->with('my-skill')->andReturn($skill);

        $tool = new SkillReferenceReader($registry);

        $result = $tool->handle(new Request([
            'skill' => 'my-skill',
            'file' => 'guide.md',
        ]));

        $this->assertSame('# Guide Content', (string) $result);
    }

    public function test_blocks_directory_traversal(): void
    {
        file_put_contents($this->tempPath.'/legit.txt', 'ok');

        $outsidePath = sys_get_temp_dir().'/outside-skill-'.uniqid().'.txt';
        file_put_contents($outsidePath, 'forbidden');

        $skill = new Skill(
            name: 'traversal-skill',
            description: 'Test',
            instructions: 'Instructions',
            basePath: $this->tempPath,
        );

        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('get')->with('traversal-skill')->andReturn($skill);

        $tool = new SkillReferenceReader($registry);

        $result = $tool->handle(new Request([
            'skill' => 'traversal-skill',
            'file' => '../../'.basename($outsidePath),
        ]));

        $this->assertStringContainsString('Access denied', (string) $result);

        @unlink($outsidePath);
    }

    public function test_returns_error_for_unloaded_skill(): void
    {
        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('get')->with('ghost-skill')->andReturn(null);

        $tool = new SkillReferenceReader($registry);

        $result = $tool->handle(new Request([
            'skill' => 'ghost-skill',
            'file' => 'anything.md',
        ]));

        $this->assertStringContainsString("Skill 'ghost-skill' not loaded", (string) $result);
    }

    public function test_returns_error_for_skill_without_base_path(): void
    {
        $skill = new Skill(
            name: 'no-path-skill',
            description: 'No path',
            instructions: 'Instructions',
            basePath: null,
        );

        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('get')->with('no-path-skill')->andReturn($skill);

        $tool = new SkillReferenceReader($registry);

        $result = $tool->handle(new Request([
            'skill' => 'no-path-skill',
            'file' => 'guide.md',
        ]));

        $this->assertStringContainsString('does not have a base path', (string) $result);
    }

    public function test_returns_error_for_nonexistent_file(): void
    {
        $skill = new Skill(
            name: 'valid-skill',
            description: 'Valid',
            instructions: 'Instructions',
            basePath: $this->tempPath,
        );

        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('get')->with('valid-skill')->andReturn($skill);

        $tool = new SkillReferenceReader($registry);

        $result = $tool->handle(new Request([
            'skill' => 'valid-skill',
            'file' => 'nonexistent.md',
        ]));

        $this->assertStringContainsString('not found', (string) $result);
    }
}
