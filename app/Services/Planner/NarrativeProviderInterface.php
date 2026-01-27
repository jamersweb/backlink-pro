<?php

namespace App\Services\Planner;

interface NarrativeProviderInterface
{
    /**
     * Explain why this task matters
     */
    public function explainWhy(array $item): string;

    /**
     * Generate checklist steps
     */
    public function generateChecklist(array $item): array;
}


