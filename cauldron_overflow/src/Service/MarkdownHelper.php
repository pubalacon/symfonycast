<?php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;

class MarkdownHelper
{
    private $markdownParser;
    private $cache;
    private $logger;

    public function __construct(MarkdownParserInterface $markdownParser, CacheInterface $cache, bool $isDebug, LoggerInterface $logger)
    {
        $this->markdownParser = $markdownParser;
        $this->cache = $cache;
        $this->isDebug = $isDebug;
        $this->logger = $logger;
    }
    
    public function parse(string $source): string
    {
        if (stripos($source, 'cat') !== false) {
            $this->logger->info('Meow!');
        }
        
        // si on est en mode debug, on ne cache pas le texte parsé
        if ($this->isDebug) {
            return $this->markdownParser->transformMarkdown($source);
        }
        
        // on cache le contenu parsé
        return $this->cache->get('markdown_'.md5($source), function() use ($source) {
            return $this->markdownParser->transformMarkdown($source);
        });
    }
}