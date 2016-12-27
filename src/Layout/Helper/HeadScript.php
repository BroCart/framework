<?php
/**
 * Bluz Framework Component
 *
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

declare(strict_types=1);

namespace Bluz\Layout\Helper;

use Bluz\Proxy\Registry;

return
    /**
     * Set or generate <script> code for <head>
     *
     * @param  string $script
     * @return string|null
     */
    function ($script = null) {
        // it's stack for <head>
        $headScripts = Registry::get('layout:headScripts') ? : [];

        if (null === $script) {
            $headScripts = array_unique($headScripts);
            // clear system vars
            Registry::set('layout:headScripts', []);
            $headScripts = array_map([$this, 'script'], $headScripts);
            return join("\n", $headScripts);
        } else {
            $headScripts[] = $script;
            Registry::set('layout:headScripts', $headScripts);
            return null;
        }
    };
