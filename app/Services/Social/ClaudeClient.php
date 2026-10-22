<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Anthropic Messages API client for Social Studio.
 *
 * runForTool() runs a conversation until Claude calls the named "submit"
 * tool, and returns that tool's input. Web search is a server tool: the API
 * runs it, so the only things to handle here are `pause_turn` (a long search
 * turn the API hands back to be continued) and Claude finishing without
 * calling the submit tool, in which case it is asked once more with the tool
 * forced.
 */
class ClaudeClient
{
    private const VERSION = '2023-06-01';

    public array $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'web_searches' => 0];

    public function __construct(
        private string $apiKey,
        private string $model,
        private int $timeout = 240,
    ) {
    }

    public static function fromSettings(): self
    {
        $key = SocialBrand::apiKey();
        if (!$key) {
            throw new RuntimeException('No Claude API key. Add it in Social Studio > Settings.');
        }

        return new self($key, SocialBrand::model());
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * @param array $tools      custom tools; the submit tool must be among them
     * @param bool  $webSearch  let Claude search the web first
     */
    public function runForTool(string $system, string $userPrompt, array $tools, string $submitTool, bool $webSearch = false, int $maxTokens = 8000): array
    {
        if ($webSearch) {
            $tools[] = [
                'type'          => 'web_search_20250305',
                'name'          => 'web_search',
                'max_uses'      => 4,
                'user_location' => ['type' => 'approximate', 'country' => 'PK', 'timezone' => 'Asia/Karachi'],
            ];
        }

        $messages = [['role' => 'user', 'content' => $userPrompt]];
        $forced = !$webSearch; // without search there is nothing to do before submitting

        for ($round = 0; $round < 6; $round++) {
            $body = [
                'model'      => $this->model,
                'max_tokens' => $maxTokens,
                'system'     => $system,
                'messages'   => $messages,
                'tools'      => $tools,
            ];
            if ($forced) {
                $body['tool_choice'] = ['type' => 'tool', 'name' => $submitTool];
            }

            $response = $this->send($body);
            $content = $response['content'] ?? [];

            foreach ($content as $block) {
                if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === $submitTool) {
                    return $block['input'] ?? [];
                }
            }

            $messages[] = ['role' => 'assistant', 'content' => $content];
            $stop = $response['stop_reason'] ?? '';

            if ($stop === 'pause_turn') {
                continue; // the API paused a long server-tool turn; send it back as is
            }

            if ($stop === 'max_tokens') {
                throw new RuntimeException('Claude ran out of output space before finishing the post.');
            }

            // Finished talking (or tried another tool) without submitting.
            $messages[] = ['role' => 'user', 'content' => $this->toolResultsFor($content) ?: 'Now submit the result with the ' . $submitTool . ' tool.'];
            $forced = true;
        }

        throw new RuntimeException('Claude did not return a result after several rounds.');
    }

    /**
     * Any client-side tool call other than the submit tool gets an error result
     * so the conversation stays well formed.
     */
    private function toolResultsFor(array $content): array
    {
        $results = [];
        foreach ($content as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                $results[] = [
                    'type'        => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content'     => 'Not available. Submit the result now.',
                    'is_error'    => true,
                ];
            }
        }
        if ($results) {
            $results[] = ['type' => 'text', 'text' => 'Submit the result now.'];
        }

        return $results;
    }

    private function send(array $body): array
    {
        $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => self::VERSION,
                'content-type'      => 'application/json',
            ])
            ->timeout($this->timeout)
            ->retry(2, 3000, function ($exception, $request) {
                // Retry overloads and rate limits, not bad requests.
                $status = optional($exception->response ?? null)->status();

                return in_array($status, [429, 500, 502, 503, 529], true);
            }, throw: false)
            ->post(rtrim(config('services.anthropic.base_url') ?: 'https://api.anthropic.com', '/') . '/v1/messages', $body);

        if (!$response->successful()) {
            $message = $response->json('error.message') ?: $response->body();
            throw new RuntimeException('Claude API error (' . $response->status() . '): ' . mb_substr((string) $message, 0, 500));
        }

        $json = $response->json();
        $u = $json['usage'] ?? [];
        $this->usage['input_tokens'] += (int) ($u['input_tokens'] ?? 0);
        $this->usage['output_tokens'] += (int) ($u['output_tokens'] ?? 0);
        $this->usage['web_searches'] += (int) ($u['server_tool_use']['web_search_requests'] ?? 0);

        return $json;
    }
}
