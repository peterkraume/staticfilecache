<?php

/**
 * Force the cache for special pages.
 */

declare(strict_types=1);

namespace SFC\Staticfilecache\Cache\Listener;

use Psr\Http\Message\ServerRequestInterface;
use SFC\Staticfilecache\Event\CacheRuleEvent;
use SFC\Staticfilecache\Event\ForceStaticFileCacheEvent;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Force the cache for special pages.
 */
class ForceStaticCacheListener
{

    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * PrepareMiddleware constructor.
     * @param \Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    public function __construct(\Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(CacheRuleEvent $event): void
    {
        if ($event->isSkipProcessing() && $this->isForceCacheUri($GLOBALS['TSFE'], $event->getRequest())) {
            $event->setSkipProcessing(false);
            $event->truncateExplanations();

            if (!\is_array($GLOBALS['TSFE']->config['INTincScript'])) {
                // Avoid exceptions in recursivelyReplaceIntPlaceholdersInContent
                $GLOBALS['TSFE']->config['INTincScript'] = [];
            }

            // render the plugins in the output
            $GLOBALS['TSFE']->INTincScript();
        }
    }

    /**
     * Is force cache URI?
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    protected function isForceCacheUri(?TypoScriptFrontendController $frontendController, ServerRequestInterface $request): bool
    {
        if (!is_object($frontendController)) {
            return false;
        }

        $forceStatic = (bool)($frontendController->page['tx_staticfilecache_cache_force'] ?? false);
        $event = new ForceStaticFileCacheEvent($forceStatic, $frontendController, $request);
        $this->eventDispatcher->dispatch($event);

        return $event->isForceStatic();
    }
}
