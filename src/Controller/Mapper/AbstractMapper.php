<?php
/**
 * Bluz Framework Component
 *
 * @copyright Bluz PHP Team
 * @link      https://github.com/bluzphp/framework
 */

declare(strict_types=1);

namespace Bluz\Controller\Mapper;

use Bluz\Application\Application;
use Bluz\Common\Exception\CommonException;
use Bluz\Common\Exception\ComponentException;
use Bluz\Controller\Controller;
use Bluz\Controller\ControllerException;
use Bluz\Crud\AbstractCrud;
use Bluz\Http\Exception\ForbiddenException;
use Bluz\Http\Exception\NotAcceptableException;
use Bluz\Http\Exception\NotAllowedException;
use Bluz\Http\Exception\NotImplementedException;
use Bluz\Http\RequestMethod;
use Bluz\Proxy\Acl;
use Bluz\Proxy\Request;
use Bluz\Proxy\Router;

/**
 * Mapper for controller
 *
 * @package  Bluz\Rest
 * @author   Anton Shevchuk
 */
abstract class AbstractMapper
{
    /**
     * @var string HTTP Method
     */
    protected $method = RequestMethod::GET;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var string
     */
    protected $controller;

    /**
     * @var array identifier
     */
    protected $primary;

    /**
     * @var string relation list
     */
    protected $relation;

    /**
     * @var string relation Id
     */
    protected $relationId;

    /**
     * @var array params of query
     */
    protected $params = [];

    /**
     * @var array query data
     */
    protected $data = [];

    /**
     * @var AbstractCrud instance of CRUD
     */
    protected $crud;

    /**
     * [
     *     METHOD => Link {
     *         'module' => 'module',
     *         'controller' => 'controller',
     *         'acl' => 'privilege',
     *         'fields' => ['id', ... ]
     *     },
     * ]
     *
     * @var Link[]
     */
    protected $map = [];

    /**
     * Prepare params
     *
     * @return array
     */
    abstract protected function prepareParams(): array;

    /**
     * @param AbstractCrud $crud
     */
    public function __construct(AbstractCrud $crud)
    {
        $this->crud = $crud;
    }

    /**
     * Add mapping data
     *
     * @param  string $method
     * @param  string $module
     * @param  string $controller
     * @return Link
     */
    public function addMap($method, $module, $controller): Link
    {
        return $this->map[strtoupper($method)] = new Link($module, $controller);
    }

    /**
     * Add param to data, for example - setup foreign keys on fly
     *
     * @param  string $name
     * @param  string $value
     *
     * @return void
     */
    public function addParam($name, $value): void
    {
        $this->data[$name] = $value;
    }

    /**
     * Add mapping for HEAD method
     *
     * @param  string $module
     * @param  string $controller
     * @return Link
     */
    public function head($module, $controller): Link
    {
        return $this->addMap(RequestMethod::HEAD, $module, $controller);
    }

    /**
     * Add mapping for GET method
     *
     * @param string $module
     * @param string $controller
     * @return Link
     */
    public function get(string $module, string $controller): Link
    {
        return $this->addMap(RequestMethod::GET, $module, $controller);
    }

    /**
     * Add mapping for POST method
     *
     * @param  string $module
     * @param  string $controller
     * @return Link
     */
    public function post(string $module, string $controller): Link
    {
        return $this->addMap(RequestMethod::POST, $module, $controller);
    }

    /**
     * Add mapping for PATCH method
     *
     * @param  string $module
     * @param  string $controller
     * @return Link
     */
    public function patch(string $module, string $controller): Link
    {
        return $this->addMap(RequestMethod::PATCH, $module, $controller);
    }

    /**
     * Add mapping for PUT method
     *
     * @param  string $module
     * @param  string $controller
     * @return Link
     */
    public function put(string $module, string $controller): Link
    {
        return $this->addMap(RequestMethod::PUT, $module, $controller);
    }

    /**
     * Add mapping for DELETE method
     *
     * @param  string $module
     * @param  string $controller
     * @return Link
     */
    public function delete(string $module, string $controller): Link
    {
        return $this->addMap(RequestMethod::DELETE, $module, $controller);
    }

    /**
     * Add mapping for OPTIONS method
     *
     * @param  string $module
     * @param  string $controller
     * @return Link
     */
    public function options(string $module, string $controller): Link
    {
        return $this->addMap(RequestMethod::OPTIONS, $module, $controller);
    }

    /**
     * Run
     *
     * @return Controller
     * @throws ComponentException
     * @throws CommonException
     * @throws ControllerException
     * @throws ForbiddenException
     * @throws NotAllowedException
     * @throws NotAcceptableException
     * @throws NotImplementedException
     */
    public function run(): Controller
    {
        $this->prepareRequest();
        return $this->dispatch();
    }

    /**
     * Prepare request for processing
     *
     * @throws ControllerException
     */
    protected function prepareRequest(): void
    {
        // HTTP method
        $method = Request::getMethod();
        $this->method = strtoupper($method);

        // get path
        // %module% / %controller% / %id% / %relation% / %id%
        $path = Router::getCleanUri();

        $this->params = explode('/', rtrim($path, '/'));

        // module
        $this->module = array_shift($this->params);

        // controller
        $this->controller = array_shift($this->params);

        $data = Request::getParams();

        unset($data['_method'], $data['_module'], $data['_controller']);

        $this->data = array_merge($data, $this->data);

        $primary = $this->crud->getPrimaryKey();

        $this->primary = array_intersect_key($this->data, array_flip($primary));
    }

    /**
     * Dispatch REST or CRUD controller
     *
     * @return mixed
     * @throws ComponentException
     * @throws CommonException
     * @throws ControllerException
     * @throws ForbiddenException
     * @throws NotAllowedException
     * @throws NotAcceptableException
     * @throws NotImplementedException
     */
    protected function dispatch()
    {
        // check implementation
        if (!isset($this->map[$this->method])) {
            throw new NotImplementedException;
        }

        $link = $this->map[$this->method];

        // check permissions
        if (!Acl::isAllowed($this->module, $link->getAcl())) {
            throw new ForbiddenException;
        }

        $this->crud->setFields($link->getFields());

        // dispatch controller
        $result = Application::getInstance()->dispatch(
            $link->getModule(),
            $link->getController(),
            $this->prepareParams()
        );

        return $result;
    }
}
