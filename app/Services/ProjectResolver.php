<?php

namespace App\Services;

use App\Project;

class ProjectResolver
{
    /**
     * @param string $slug
     *
     * @return Project
     */
    public function bySlug(string $slug): Project
    {
        return Project::findBySlug($slug);
    }
}
