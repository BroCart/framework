<?php
/**
 * @copyright Bluz PHP Team
 * @link      https://github.com/bluzphp/skeleton
 */

namespace Bluz\Tests\Proxy;

use Bluz\Router\Router as Target;
use Bluz\Proxy\Router as Proxy;
use Bluz\Tests\FrameworkTestCase;

/**
 * Proxy Test
 *
 * @package  Bluz\Tests\Proxy
 * @author   Anton Shevchuk
 */
class RouterTest extends FrameworkTestCase
{
    public function testGetAlreadyInitedProxyInstance()
    {
        self::assertInstanceOf(Target::class, Proxy::getInstance());
    }

    /**
     * @expectedException \Bluz\Common\Exception\ComponentException
     */
    public function testLazyInitialInstanceShouldThrowError()
    {
        Proxy::resetInstance();
        Proxy::getInstance();
    }
}
