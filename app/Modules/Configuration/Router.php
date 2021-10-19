<?php


namespace App\Modules\Configuration;


use App\Model;
use Exception;

class Router extends Model
{

    /**
     * @var array
     */
    protected array $routes = [];

    /**
     * @var string
     */
    protected string $method;

    /**
     * @var string
     */
    protected string $path;

    /**
     * @var array
     */
    protected array $params = [];

    /**
     * @var bool
     */
    protected bool $hasConn = false;

    /**
     * Router constructor.
     * @param string $method
     * @param string $path
     * @throws Exception
     */
    public function __construct(string $method, string $path)
    {
        parent::__construct();

        $this->set('method', $method);
        $this->set('path', $path);
    }

    /**
     * @param string $method
     * @param string $route
     * @param callable $action
     */
    public function add(string $method, string $route, callable $action)
    {
        $this->routes[$method][$route] = $action;
    }

    /**
     * @return bool|mixed
     */
    public function handler()
    {
        if (empty($this->routes[$this->method])) {
            return false;
        }

        if (isset($this->routes[$this->method][$this->path])) {
            return $this->routes[$this->method][$this->path];
        }

        foreach ($this->routes[$this->method] as $route => $action) {
            $result = $this->checkUrl($route, $this->path);
            if ($result >= 1) {
                return $action;
            }
        }

        return false;
    }

    /**
     * @param string $route
     * @param string $path
     * @return false|int
     */
    private function checkUrl(string $route, string $path)
    {
        preg_match_all('/\{([^\}]*)\}/', $route, $variables);

        $regex = str_replace('/', '\/', $route);

        foreach ($variables[0] as $k => $variable) {
            $replacement = '([a-zA-Z0-9\-\_\ ]+)';
            $regex = str_replace($variable, $replacement, $regex);
        }

        $regex = preg_replace('/{([a-zA-Z]+)}/', '([a-zA-Z0-9+])', $regex);
        $result = preg_match('/^' . $regex . '$/', $path, $params);
        $this->set('params', $params);

        return $result;
    }
}