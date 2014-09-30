<?php
/**
 * Bluz Framework Component
 *
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

/**
 * @namespace
 */
namespace Bluz\View;

use Bluz\Auth\AbstractRowEntity;
use Bluz\Common\Container;
use Bluz\Common\Helper;
use Bluz\Common\Options;

/**
 * View
 *
 * @package  Bluz\View
 *
 * @method string ahref(string $text, mixed $href, array $attributes = [])
 * @method string api(string $module, string $method, $params = array())
 * @method string attributes(array $attributes = [])
 * @method string baseUrl(string $file = null)
 * @method array|null breadCrumbs(array $data = [])
 * @method string checkbox($name, $value = null, $checked = false, array $attributes = [])
 * @method string|bool controller(string $controller = null)
 * @method string|View dispatch($module, $controller, $params = array())
 * @method string exception(\Exception $exception)
 * @method string|null headScript(string $script = null)
 * @method string|null headStyle(string $style = null, $media = 'all')
 * @method string|View meta(string $name = null, string $content = null)
 * @method string|bool module(string $module = null)
 * @method string|View link(string $src = null, string $rel = 'stylesheet')
 * @method string partial($__template, $__params = array())
 * @method string partialLoop($template, $data = [], $params = [])
 * @method string radio($name, $value = null, $checked = false, array $attributes = [])
 * @method string redactor($selector, array $settings = [])
 * @method string script(string $script)
 * @method string select($name, array $options = [], $selected = null, array $attributes = [])
 * @method string style(string $style, $media = 'all')
 * @method string|View title(string $title = null, $position = 'replace', $separator = ' :: ')
 * @method string|null url(string $module, string $controller, array $params = [], bool $checkAccess = false)
 * @method AbstractRowEntity|null user()
 * @method void widget($module, $widget, $params = [])
 *
 * @author   Anton Shevchuk, ErgallM
 * @created  08.07.11 11:49
 */
class View implements ViewInterface, \JsonSerializable
{
    use Container;
    use Options;
    use Helper;

    /**
     * Constants for define positions
     */
    const POS_PREPEND = 'prepend';
    const POS_REPLACE = 'replace';
    const POS_APPEND = 'append';

    /**
     * @var string base url
     */
    protected $baseUrl;

    /**
     * @var string path to template
     */
    protected $path;

    /**
     * @var array paths to partial
     */
    protected $partialPath = [];

    /**
     * @var string template name
     */
    protected $template;

    /**
     * __construct
     *
     * @return self
     */
    public function __construct()
    {
        // initial default helper path
        $this->addHelperPath(dirname(__FILE__) . '/Helper/');
    }

    /**
     * __sleep
     *
     * @return string[]
     */
    public function __sleep()
    {
        return ['baseUrl', 'container', 'path', 'template'];
    }

    /**
     * Is callable
     *
     * @return string
     */
    public function __invoke()
    {
        return $this->render();
    }

    /**
     * Render like string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     * @return $this|ViewInterface
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $file
     * @return $this|ViewInterface
     */
    public function setTemplate($file)
    {
        $this->template = $file;
        return $this;
    }

    /**
     * Add partial path for use inside partial and partialLoop helpers
     *
     * @param string $path
     * @return View
     */
    public function addPartialPath($path)
    {
        $this->partialPath[] = $path;
        return $this;
    }

    /**
     * Render
     *
     * @throws ViewException
     * @return string
     */
    public function render()
    {
        ob_start();
        try {
            if (!file_exists($this->path . '/' . $this->template)
                or !is_file($this->path . '/' . $this->template)) {
                throw new ViewException("Template '{$this->template}' not found");
            }
            extract($this->container);
            require $this->path . '/' . $this->template;
        } catch (\Exception $e) {
            // clean output
            ob_end_clean();
            // @codeCoverageIgnoreStart
            if (app()->isDebug()) {
                return $e->getMessage() ."\n<br/>". $e->getTraceAsString();
            }
            // @codeCoverageIgnoreEnd
            // nothing for production
            return '';
        }
        return ob_get_clean();
    }
}
