<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class GameData
{
    private array $tables = [];

    public function table(string $name): array
    {
        return $this->tables[$name] ??= $this->load($name);
    }

    private function load(string $name): array
    {
        $path = resource_path("data/game/{$name}.json");
        $version = filemtime($path);

        return Cache::rememberForever("wow:data:{$name}:{$version}", fn () => json_decode(
            file_get_contents($path), true, flags: JSON_THROW_ON_ERROR
        ));
    }
}
