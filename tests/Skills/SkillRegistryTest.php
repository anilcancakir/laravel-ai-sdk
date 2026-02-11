<?php

namespace Tests\Skills;

use Laravel\Ai\Skills\Skill;
use Laravel\Ai\Skills\SkillRegistry;
use Mockery;
use PHPUnit\Framework\TestCase;

class SkillRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_it_can_load_a_skill()
    {
        $discovery = Mockery::mock('Laravel\Ai\Skills\SkillDiscovery');
        $skill = new Skill('test-skill', 'Description', 'Instructions');

        $discovery->shouldReceive('resolve')->with('test-skill')->once()->andReturn($skill);

        $registry = new SkillRegistry($discovery);

        $this->assertFalse($registry->isLoaded('test-skill'));

        $loadedSkill = $registry->load('test-skill');

        $this->assertSame($skill, $loadedSkill);
        $this->assertTrue($registry->isLoaded('test-skill'));
        $this->assertSame($skill, $registry->get('test-skill'));
    }

    public function test_it_returns_null_when_skill_cannot_be_resolved()
    {
        $discovery = Mockery::mock('Laravel\Ai\Skills\SkillDiscovery');
        $discovery->shouldReceive('resolve')->with('unknown')->once()->andReturn(null);

        $registry = new SkillRegistry($discovery);

        $this->assertNull($registry->load('unknown'));
        $this->assertFalse($registry->isLoaded('unknown'));
    }

    public function test_it_can_get_all_loaded_skills()
    {
        $discovery = Mockery::mock('Laravel\Ai\Skills\SkillDiscovery');
        $skill1 = new Skill('skill-1', 'Desc 1', 'Instr 1');
        $skill2 = new Skill('skill-2', 'Desc 2', 'Instr 2');

        $discovery->shouldReceive('resolve')->with('skill-1')->andReturn($skill1);
        $discovery->shouldReceive('resolve')->with('skill-2')->andReturn($skill2);

        $registry = new SkillRegistry($discovery);
        $registry->load('skill-1');
        $registry->load('skill-2');

        $loaded = $registry->getLoaded();

        $this->assertCount(2, $loaded);
        $this->assertArrayHasKey('skill-1', $loaded);
        $this->assertArrayHasKey('skill-2', $loaded);
    }

    public function test_it_generates_instructions_xml_in_full_mode()
    {
        $discovery = Mockery::mock('Laravel\Ai\Skills\SkillDiscovery');
        $skill = new Skill('test-skill', 'Description', 'Instructions');

        $discovery->shouldReceive('resolve')->with('test-skill')->andReturn($skill);

        $registry = new SkillRegistry($discovery);
        $registry->load('test-skill');

        $expected = '<skill name="test-skill">'.PHP_EOL.'Instructions'.PHP_EOL.'</skill>';
        $this->assertEquals($expected, $registry->instructions('full'));
    }

    public function test_it_generates_instructions_xml_in_lite_mode()
    {
        $discovery = Mockery::mock('Laravel\Ai\Skills\SkillDiscovery');
        $skill = new Skill('test-skill', 'Description', 'Instructions');

        $discovery->shouldReceive('resolve')->with('test-skill')->andReturn($skill);

        $registry = new SkillRegistry($discovery);
        $registry->load('test-skill');

        $expected = '<skill name="test-skill" description="Description" />';
        $this->assertEquals($expected, $registry->instructions('lite'));
    }

    public function test_it_generates_empty_string_in_none_mode()
    {
        $discovery = Mockery::mock('Laravel\Ai\Skills\SkillDiscovery');
        $skill = new Skill('test-skill', 'Description', 'Instructions');

        $discovery->shouldReceive('resolve')->with('test-skill')->andReturn($skill);

        $registry = new SkillRegistry($discovery);
        $registry->load('test-skill');

        $this->assertEquals('', $registry->instructions('none'));
        $this->assertEquals('', $registry->instructions(null));
    }
}
