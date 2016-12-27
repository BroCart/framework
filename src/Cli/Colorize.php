<?php
/**
 * Bluz Framework Component
 *
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

declare(strict_types=1);

namespace Bluz\Cli;

/**
 * Colorize text for CLI
 *
 * @package  Bluz\Cli
 * @author   Anton Shevchuk
 */
class Colorize
{
    /**
     * @var array list of text colors
     */
    protected static $colors = [
        'black' => '30',
        'red' => '31',
        'green' => '32',
        'yellow' => '33',
        'blue' => '34',
        'magenta' => '35',
        'cyan' => '36',
        'gray' => '37',
    ];

    /**
     * @var array list of background colors
     */
    protected static $background = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'gray' => '47'
    ];

    /**
     * Return string colorized for Linux console
     *
     * @param  string $text
     * @param  string $foreground
     * @param  string $background
     * @param  bool   $bold
     * @param  bool   $underline
     * @return string
     */
    public static function text($text, $foreground = null, $background = null, $bold = false, $underline = false)
    {
        $colored = "\033[";

        if ($bold) {
            $colored .= '1;';
        }

        if ($underline) {
            $colored .= '4;';
        }

        if ($foreground !== null && isset(self::$colors[$foreground])) {
            $colored .= self::$colors[$foreground] . ";";
        }
        if ($background !== null && isset(self::$background[$background])) {
            $colored .= self::$background[$background] . ";";
        }

        $colored = rtrim($colored, ';');

        $colored .= "m" . $text . "\033[0m";

        return $colored;
    }
}
