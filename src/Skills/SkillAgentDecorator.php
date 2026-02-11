<?php

namespace Laravel\Ai\Skills;

use Illuminate\Broadcasting\Channel;
use Illuminate\Container\Container;
use Illuminate\Support\Stringable;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasSkills;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\QueuedAgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;

class SkillAgentDecorator implements Agent, Conversational, HasSkills, HasTools
{
    /**
     * Create a new skill agent decorator instance.
     */
    public function __construct(
        protected Agent $agent
    ) {
        $this->bootSkills();
    }

    /**
     * Boot the agent skills.
     */
    protected function bootSkills(): void
    {
        if (! $this->agent instanceof HasSkills) {
            return;
        }

        $registry = Container::getInstance()->make(SkillRegistry::class);

        foreach ($this->agent->skills() as $skill) {
            $registry->load($skill);
        }
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $instructions = $this->agent->instructions();

        if ($this->agent instanceof HasSkills) {
            $registry = Container::getInstance()->make(SkillRegistry::class);
            $mode = $this->agent->skillDiscoveryMode()->value;
            $skillInstructions = $registry->instructions($mode);

            if (! empty($skillInstructions)) {
                $instructions = (string) $instructions.PHP_EOL.PHP_EOL.$skillInstructions;
            }
        }

        return $instructions;
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): iterable
    {
        $tools = [];

        if ($this->agent instanceof HasTools) {
            $tools = (array) $this->agent->tools();
        }

        if ($this->agent instanceof HasSkills) {
            $registry = Container::getInstance()->make(SkillRegistry::class);

            $tools = array_merge($tools, $registry->tools());
        }

        return $tools;
    }

    /**
     * Get the skills available to the agent.
     */
    public function skills(): iterable
    {
        if ($this->agent instanceof HasSkills) {
            return $this->agent->skills();
        }

        return [];
    }

    /**
     * Get the skill discovery mode.
     */
    public function skillDiscoveryMode(): SkillDiscoveryMode
    {
        if ($this->agent instanceof HasSkills) {
            return $this->agent->skillDiscoveryMode();
        }

        return SkillDiscoveryMode::Lite;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     */
    public function messages(): iterable
    {
        if ($this->agent instanceof Conversational) {
            return $this->agent->messages();
        }

        return [];
    }

    /**
     * Invoke the agent with a given prompt.
     */
    public function prompt(
        string $prompt,
        array $attachments = [],
        ?string $provider = null,
        ?string $model = null
    ): AgentResponse {
        return $this->agent->prompt($prompt, $attachments, $provider, $model);
    }

    /**
     * Invoke the agent with a given prompt and return a streamable response.
     */
    public function stream(
        string $prompt,
        array $attachments = [],
        ?string $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        return $this->agent->stream($prompt, $attachments, $provider, $model);
    }

    /**
     * Invoke the agent in a queued job.
     */
    public function queue(
        string $prompt,
        array $attachments = [],
        array|string|null $provider = null,
        ?string $model = null
    ): QueuedAgentResponse {
        return $this->agent->queue($prompt, $attachments, $provider, $model);
    }

    /**
     * Invoke the agent with a given prompt and broadcast the streamed events.
     */
    public function broadcast(
        string $prompt,
        Channel|array $channels,
        array $attachments = [],
        bool $now = false,
        ?string $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        return $this->agent->broadcast($prompt, $channels, $attachments, $now, $provider, $model);
    }

    /**
     * Invoke the agent with a given prompt and broadcast the streamed events immediately.
     */
    public function broadcastNow(
        string $prompt,
        Channel|array $channels,
        array $attachments = [],
        ?string $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        return $this->agent->broadcastNow($prompt, $channels, $attachments, $provider, $model);
    }

    /**
     * Queue the agent with a given prompt and broadcast the streamed events.
     */
    public function broadcastOnQueue(
        string $prompt,
        Channel|array $channels,
        array $attachments = [],
        ?string $provider = null,
        ?string $model = null
    ): QueuedAgentResponse {
        return $this->agent->broadcastOnQueue($prompt, $channels, $attachments, $provider, $model);
    }

    /**
     * Get the agent instance.
     */
    public function agent(): Agent
    {
        return $this->agent;
    }
}
