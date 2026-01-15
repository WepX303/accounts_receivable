<?php

namespace app;

use Illuminate\Foundation\Application as LaravelApplication;

class BaseApplication extends LaravelApplication
{
    protected $namespace = 'app\\';

    public function path($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'src/app'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
