<?php

namespace App\Services;

use App\Commit;
use GuzzleHttp\Client;

class GithubStatusService
{
    /** @var Client */
    protected $client;

    /**
     * GithubStatusService constructor.
     */
    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'Authorization' => 'token '.config('services.github.token'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'base_uri' => 'https://api.github.com',
        ]);
    }

    /**
     * Updates the status of a commit.
     *
     * @param Commit      $commit
     * @param string      $state
     * @param null|string $message
     * @param null|string $url
     */
    public function postStatus(Commit $commit, string $state, ?string $message, ?string $url = null)
    {
        $data = [
            'state' => $state,
            'context' => config('app.contextprefix').'/'.$commit->task,
        ];
        if (!is_null($message)) {
            $data['description'] = $message;
        }
        if (!is_null($url)) {
            $data['target_url'] = $url;
        }

        $this->client->post(
            "/repos/{$commit->project->repo}/statuses/{$commit->hash}",
            [
                'json' => $data,
            ]
        );
    }
}
