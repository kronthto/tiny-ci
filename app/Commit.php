<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $hash
 * @property int $project_id
 * @property bool|null $passing
 * @property string|null $joblog
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property Project $project
 */
class Commit extends Model
{
    protected $guarded = ['id', 'passing', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function getSecretToken(): string
    {
        return hash_hmac('sha256', 'commit'.$this->id,
            base64_encode(app(\Illuminate\Encryption\Encrypter::class)->getKey())
        );
    }

    /**
     * Builds the URL the build log can be accessed at.
     *
     * @return string
     */
    public function buildUrl(): string
    {
        return url()->route('buildLog', ['commit' => $this, 'token' => $this->getSecretToken()]);
    }
}
