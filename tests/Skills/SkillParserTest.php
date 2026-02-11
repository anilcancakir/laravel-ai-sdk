<?php

namespace Tests\Skills;

use Laravel\Ai\Skills\SkillParser;
use Tests\TestCase;

class SkillParserTest extends TestCase
{
    public function test_parses_valid_skill_md()
    {
        $markdown = <<<'MD'
---
name: my-skill
description: A useful skill
tools: [weather]
triggers: [weather, forecast]
version: 1.0.0
constraints:
  os: macos
---
# Instructions
Do something.
MD;

        $skill = SkillParser::parse($markdown, 'remote', '/base');

        $this->assertNotNull($skill);
        $this->assertSame('my-skill', $skill->name);
        $this->assertSame('A useful skill', $skill->description);
        $this->assertSame(['weather'], $skill->tools);
        $this->assertSame(['weather', 'forecast'], $skill->triggers);
        $this->assertSame('1.0.0', $skill->version);
        $this->assertSame(['os' => 'macos'], $skill->constraints);
        $this->assertSame('remote', $skill->source);
        $this->assertSame('/base', $skill->basePath);
        $this->assertSame('# Instructions'.PHP_EOL.'Do something.', $skill->instructions);
    }

    public function test_returns_null_for_missing_name()
    {
        $markdown = <<<'MD'
---
description: Missing name
---
Body
MD;

        $this->assertNull(SkillParser::parse($markdown));
    }

    public function test_returns_null_for_missing_description()
    {
        $markdown = <<<'MD'
---
name: Missing description
---
Body
MD;

        $this->assertNull(SkillParser::parse($markdown));
    }

    public function test_returns_null_for_invalid_yaml()
    {
        $markdown = <<<'MD'
---
name: invalid: [yaml
---
Body
MD;

        $this->assertNull(SkillParser::parse($markdown));
    }

    public function test_handles_optional_fields()
    {
        $markdown = <<<'MD'
---
name: simple
description: simple desc
---
body
MD;

        $skill = SkillParser::parse($markdown);

        $this->assertNotNull($skill);
        $this->assertSame([], $skill->tools);
        $this->assertSame([], $skill->triggers);
        $this->assertNull($skill->version);
        $this->assertSame([], $skill->constraints);
    }

    public function test_preserves_markdown_instructions()
    {
        $markdown = <<<'MD'
---
name: md
description: md desc
---
# Header
- list item
MD;

        $skill = SkillParser::parse($markdown);

        $this->assertSame('# Header'.PHP_EOL.'- list item', $skill->instructions);
    }
}
