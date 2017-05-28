<?php

namespace Tests\Unit;

use App\Commit;
use App\Project;
use App\Services\GithubStatusService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GithubStatusTest extends TestCase
{
    /**
     * Check the requests that would be sent to the Github API.
     */
    public function testPostFormatIsAsAcceptedByGhApi()
    {
        /** @var GithubStatusService $service */
        $service = $this->app->make(GithubStatusService::class);

        $reflectionClass = new \ReflectionClass($service);
        $clientProp = $reflectionClass->getProperty('client');
        $clientProp->setAccessible(true);

        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'base_uri' => 'https://api.github.com',
            'handler' => $stack,
        ]);

        $clientProp->setValue($service, $client);

        $project = new Project([
            'repo' => 'vendor/repo',
        ]);
        $commit = new Commit([
            'project' => $project,
            'hash' => 'f55429aaa7b06e73ab588f84cd4f89636891f50e',
            'task' => 'pushorpr'
        ]);

        $service->postStatus($commit, 'success', 'It worked',
            'http://foo.bar/baz');

        $this->assertSame(1, sizeof($container));

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = reset($container)['request'];

        $this->assertEquals(
            '/repos/vendor/repo/statuses/f55429aaa7b06e73ab588f84cd4f89636891f50e',
            $request->getUri()->getPath()
        );

        $payload = \GuzzleHttp\json_decode($request->getBody()->getContents());

        $context = explode('/', $payload->context);
        $this->assertNotEmpty($context[0]);
        $this->assertEquals(config('app.contextprefix'), $context[0]);
        $this->assertEquals('pushorpr', $context[1]);

        $this->assertNotEmpty($payload->context);
        $this->assertEquals('success', $payload->state);
        $this->assertEquals('It worked', $payload->description);
        $this->assertEquals('http://foo.bar/baz', $payload->target_url);
    }
}
