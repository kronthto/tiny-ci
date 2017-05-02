<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $secret
 * @property string $slug
 * @property string $repo
 * @property Commit[] $commits
 */
class Project extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $hidden = ['secret'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function commits()
    {
        return $this->hasMany(Commit::class);
    }
}
