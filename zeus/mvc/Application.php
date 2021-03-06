<?php

namespace zeus\mvc;

use zeus\http\Response;
use zeus\http\XssWapperRequest;
use zeus\mvc\exception\ControllerNotFoundException;

class Application
{
    private static $instance;

    private $request;
    private $response;

    /**
     * @return \zeus\mvc\Application
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function dispatch($url_path)
    {
        $url_path_data = parse_url($url_path);
        $path = isset($url_path_data["path"]) ? $url_path_data['path'] : "/";
        if (isset($url_path_data['query'])) {
            parse_str($url_path_data['query'], $data);
            $this->request->setData($data);
        }

        $controller = null;
        try {
            $router = new Router($path);

            $controllerClass = $router->getController();
            $controller = new $controllerClass();
            if (!($controller instanceof Controller)) {
                throw new ControllerNotFoundException("{$controllerClass} 控制器不是系统控制器子类");
            }
            call_user_func_array(array($controller, $router->getAction()), $router->getParams());
        } catch (\Exception $e) {
            ob_clean();
            if (is_null($controller) || !($controller instanceof Controller)) {
                throw $e;
            }
            $controller->errorHandler($e);
        }
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    protected function __construct()
    {
        ob_start();

        $this->request = new XssWapperRequest();
        $this->response = new Response();
    }

    public function __destruct()
    {
        ob_end_flush();
    }
}