<?php

namespace Mbsoft\SemanticScholar\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Mbsoft\SemanticScholar\Builder;
use Mbsoft\SemanticScholar\DTOs\Author;
use Mbsoft\SemanticScholar\DTOs\Paper;
use Mbsoft\SemanticScholar\Http\Client;

/**
 * @see \Mbsoft\SemanticScholar\SemanticScholar
 *
 * @method static Builder papers()
 * @method static Builder authors()
 * @method static Builder venues()
 * @method static Collection searchPapers(string $query, int $limit = 10)
 * @method static Collection searchAuthors(string $query, int $limit = 10)
 * @method static Paper|null findPaperByDoi(string $doi)
 * @method static Paper|null findPaperByArxiv(string $arxivId)
 * @method static Author|null findAuthorByOrcid(string $orcid)
 * @method static Client client()
 * @method static bool test()
 * @method static bool healthCheck()
 * @method static array getUsageStats()
 */
class SemanticScholar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'semantic-scholar';
    }
}
