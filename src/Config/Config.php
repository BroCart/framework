<?php
/**
 * Bluz Framework Component
 *
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

declare(strict_types=1);

namespace Bluz\Config;

/**
 * Config
 *
 * @package  Bluz\Config
 * @author   Anton Shevchuk
 * @link     https://github.com/bluzphp/framework/wiki/Config
 */
class Config
{
    /**
     * @var array configuration data
     */
    protected $config;

    /**
     * @var array modules configuration data
     */
    protected $modules;

    /**
     * @var string path to configuration files
     */
    protected $path;

    /**
     * @var string environment
     */
    protected $environment;

    /**
     * Set path to configuration files
     *
     * @param  string $path
     * @return void
     * @throws ConfigException
     */
    public function setPath($path)
    {
        if (!is_dir($path)) {
            throw new ConfigException('Configuration directory is not exists');
        }
        $this->path = rtrim($path, '/');
    }

    /**
     * Set application environment
     *
     * @param  string $environment
     * @return void
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Load configuration
     *
     * @return void
     * @throws ConfigException
     */
    public function init()
    {
        if (!$this->path) {
            throw new ConfigException('Configuration directory is not setup');
        }

        $this->config = $this->loadFiles($this->path .'/configs/default');

        if ($this->environment) {
            $customConfig = $this->loadFiles($this->path . '/configs/' . $this->environment);
            $this->config = array_replace_recursive($this->config, $customConfig);
        }
    }

    /**
     * Load configuration file
     *
     * @param  string $path
     * @return array
     * @throws ConfigException
     */
    protected function loadFile($path)
    {
        if (!is_file($path) && !is_readable($path)) {
            throw new ConfigException("Configuration file `$path` not found");
        }
        return include $path;
    }

    /**
     * Load configuration files to array
     *
     * @param  string $path
     * @return array
     * @throws ConfigException
     */
    protected function loadFiles($path)
    {
        $config = [];

        if (!is_dir($path)) {
            throw new ConfigException("Configuration directory `$path` not found");
        }

        $iterator = new \GlobIterator(
            $path .'/*.php',
            \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_PATHNAME
        );

        foreach ($iterator as $name => $file) {
            $name = substr($name, 0, -4);
            $config[$name] = $this->loadFile($file);
        }
        return $config;
    }

    /**
     * Return configuration by key
     *
     * @param  string|null $key     Key of config
     * @param  string|null $section Section of config
     * @return array|mixed
     * @throws ConfigException
     */
    public function getData($key = null, $section = null)
    {
        // configuration is missed
        if (is_null($this->config)) {
            throw new ConfigException('System configuration is missing');
        }

        // return all configuration
        if (is_null($key)) {
            return $this->config;
        }

        // return part of configuration
        if (isset($this->config[$key])) {
            // return section of configuration
            if (!is_null($section)) {
                return $this->config[$key][$section] ?? null;
            } else {
                return $this->config[$key];
            }
        } else {
            return null;
        }
    }

    /**
     * Return module configuration by section
     *
     * @param  string $module
     * @param  string $section
     * @return mixed
     */
    public function getModuleData($module, $section = null)
    {
        if (!isset($this->modules[$module])) {
            $this->modules[$module] = $this->loadFile(
                $this->path .'/modules/'. $module .'/config.php'
            );

            if (is_null($this->config)) {
                $this->init();
            }

            if (isset($this->config["module.$module"])) {
                $this->modules[$module] = array_replace_recursive(
                    $this->modules[$module],
                    $this->config["module.$module"]
                );
            }
        }

        if (!is_null($section)) {
            return $this->modules[$module][$section] ?? null;
        }

        return $this->modules[$module];
    }
}
