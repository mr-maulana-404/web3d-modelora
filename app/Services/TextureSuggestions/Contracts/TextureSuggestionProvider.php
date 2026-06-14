<?php

namespace App\Services\TextureSuggestions\Contracts;

interface TextureSuggestionProvider
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     * @param  array<string, mixed>|null  $screenshot
     */
    public function generate(array $context, int $count, ?array $screenshot = null): array;
}
