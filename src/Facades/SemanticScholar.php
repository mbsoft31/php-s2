<?php

namespace Mbsoft\SemanticScholar\Facades;

use Illuminate\Support\Facades\Facade;
use Mbsoft\SemanticScholar\Builder;

/**
 * @method static Builder papers()
 * @method static Builder authors()
 * @method static Builder venues()
 * @method static Builder recommendations()
 * @method static array config()
 * @method static string getBaseUrl()
 * @method static bool hasApiKey()
 * @method static array getRateLimit()
 *
 * @see \Mbsoft\SemanticScholar\SemanticScholar
 */
class SemanticScholar extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'semantic-scholar';
    }
}
