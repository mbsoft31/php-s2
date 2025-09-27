<?php

namespace Mbsoft\SemanticScholar;

use Mbsoft\SemanticScholar\Http\Client;
use Mbsoft\SemanticScholar\Builders\PaperBuilder;
use Mbsoft\SemanticScholar\Builders\AuthorBuilder;

class SemanticScholar
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get papers builder
     */
    public function papers(): PaperBuilder
    {
        return app(PaperBuilder::class);
    }

    /**
     * Get authors builder
     */
    public function authors(): AuthorBuilder
    {
        return app(AuthorBuilder::class);
    }

    /**
     * Get HTTP client instance
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * Test API connection
     */
    public function test(): bool
    {
        try {
            $paper = $this->papers()->findByDoi('10.1093/mind/lix.236.433');
            return $paper !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
}
