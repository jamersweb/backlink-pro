<?php

namespace App\Support;

class Feature
{
    public static function enabled(string $key): bool
    {
        return (bool) (config("features.{$key}", false));
    }
}
