<?php

namespace App\Services;

use App\Project;
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
     * @param Project     $project
     * @param string      $sha
     * @param string      $state
     * @param null|string $message
     * @param null|string $url
     */
    public function postStatus(Project $project, string $sha, string $state, ?string $message, ?string $url = null)
    {
        $data = [
            'state' => $state,
            'context' => config('app.contextprefix').'/'.$project->task,
        ];
        if (!is_null($message)) {
            $data['description'] = $message;
        }
        if (!is_null($url)) {
            $data['target_url'] = $url;
        }

        $this->client->post(
            "/repos/{$project->repo}/statuses/$sha",
            [
                'json' => $data,
            ]
        );
    }
}
