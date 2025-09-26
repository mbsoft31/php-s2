<?php
// src/Commands/SemanticScholarCommand.php

namespace Mbsoft\SemanticScholar\Commands;

use Illuminate\Console\Command;
use Mbsoft\SemanticScholar\Facades\SemanticScholar;
use Mbsoft\SemanticScholar\Exceptions\SemanticScholarException;

class SemanticScholarCommand extends Command
{
    protected $signature = 'semantic-scholar {action} {--key=} {--test} {--clear-cache}';

    protected $description = 'Manage Semantic Scholar API integration';

    public function handle()
    {
        $action = $this->argument('action');

        match($action) {
            'test' => $this->testConnection(),
            'status' => $this->showStatus(),
            'cache:clear' => $this->clearCache(),
            'key:validate' => $this->validateKey(),
            default => $this->error("Unknown action: {$action}")
        };
    }

    private function testConnection(): void
    {
        $this->info('Testing Semantic Scholar API connection...');

        try {
            $paper = SemanticScholar::papers()
                ->findByDoi('10.1093/mind/lix.236.433');

            if ($paper) {
                $this->info('✅ Connection successful!');
                $this->line("Test paper: {$paper->getTitle()}");
                $this->line("Authors: " . $paper->getAuthors()->pluck('name')->join(', '));
            }
        } catch (SemanticScholarException $e) {
            $this->error("❌ Connection failed: {$e->getMessage()}");
        }
    }

    private function showStatus(): void
    {
        $config = config('semantic-scholar');

        $this->info('Semantic Scholar Package Status');
        $this->line('================================');
        $this->line('API Key: ' . ($config['api_key'] ? '✅ Configured' : '❌ Not set'));
        $this->line('Base URL: ' . $config['base_url']);
        $this->line('Rate Limit: ' . ($config['api_key'] ? '100 req/sec' : '1 req/sec'));
        $this->line('Cache Driver: ' . $config['cache']['driver']);
        $this->line('Default TTL: ' . $config['cache']['default_ttl'] . 's');
    }

    private function clearCache(): void
    {
        $this->info('Clearing Semantic Scholar cache...');

        cache()->tags(['semantic-scholar'])->flush();

        $this->info('✅ Cache cleared successfully!');
    }

    private function validateKey(): void
    {
        $key = $this->option('key') ?: config('semantic-scholar.api_key');

        if (!$key) {
            $this->error('❌ No API key provided');
            return;
        }

        $this->info('Validating API key...');

        try {
            // Test with a simple API call
            $response = SemanticScholar::papers()->search('test')->limit(1)->get();
            $this->info('✅ API key is valid!');
        } catch (SemanticScholarException $e) {
            $this->error("❌ API key validation failed: {$e->getMessage()}");
        }
    }
}
