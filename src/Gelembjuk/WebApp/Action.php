<?php 

namespace Gelembjuk\WebApp;

use \Gelembjuk\WebApp\Request\AbstractRequest as Request;
use \Gelembjuk\WebApp\Request\PHPStandard as PHPStandard;
use \Gelembjuk\WebApp\Context as Context;
use \Gelembjuk\WebApp\Present\Presenter as Presenter;
use \Gelembjuk\WebApp\Response\ErrorResponse as ErrorResponse;
use Gelembjuk\WebApp\Response\Response;

class Action {
    public Context $context;
    protected Request $request;
    protected $endpointsIndex = [];
    protected $defaultControllerClass = null;

    public function __construct(?Context $context = null, ?Request $request = null)
    {
        if ($context === null) {
            $context = new Context();
        }
        $this->context = $context;

        if ($request === null) {
            // default request object
            $request = new PHPStandard();
        }
        $this->request = $request;
    }
    public function withDefaultController($controllerClass)
    {
        $this->defaultControllerClass = $controllerClass;
        return $this;
    }
    public function withRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    public function action(): Presenter
    {
        $response = null;
        $exception = null;

        try {
            list($controller, $method_name) = $this->buildControllerForAction();

            if ($controller === null) {
                return $this->noController();
            }

            return $controller->action($method_name);

        } catch (\Throwable $e) {
            $this->context->errorLogger->error('In App action method: ' . $e->getMessage());
            $this->context->errorLogger->error($e->getFile().':'.$e->getLine());
            $this->context->errorLogger->error($e->getTraceAsString());

            $response = new ErrorResponse("", $e);

            if ($e instanceof \Exception) {
                $exception = $e;
            }
        } 
        $present_format = $this->decideOutputFormat();

        if ($exception !== null) {
            $customResponse = $this->exceptionToResponse($exception, $present_format);

			$response = $customResponse ?? $response;
        }

        try {
            $presenter = Present\Builder::createPresenter($this->context, $response, $present_format);
            $presenter->buildOutput();
            return $presenter;
        } catch (\Throwable $e) {
            $present_format = "raw";
        }
        $presenter = Present\Builder::createPresenter($this->context, $response, $present_format);
        $presenter->buildOutput();
        return $presenter;
    }

    private function noController()
    {
        $response = new \Gelembjuk\WebApp\Response\ErrorResponse("No controller found for this endpoint", null, 404);
        $present_format = $this->decideOutputFormat();
        $presenter = Present\Builder::createPresenter($this->context, $response, $present_format);
        $presenter->buildOutput();
        return $presenter;
    }
    /**
     * This is used only if the controller is not detected from the endpoint
     */
    private function decideOutputFormat(): string
    {
        try {
            if ($this->defaultControllerClass !== null) {
                $controller = new $this->defaultControllerClass($this->context, $this->request);
            } else {
                $controller = new Controller($this->context, $this->request);
            }
            return $controller->decideOutputFormat();
        } catch (\Throwable $e) {
            $this->context->errorLogger->error('In App decideOutputFormat method: ' . $e->getMessage());
            $this->context->errorLogger->error($e->getFile().':'.$e->getLine());
            $this->context->errorLogger->error($e->getTraceAsString());
            return 'json';
        }
    }

    private function buildControllerForAction()
    {
        $parsed_controller = $this->detectControllerFromIndex();
        
        if ($parsed_controller === null) {
            $parsed_controller = $this->detectControllerFromInput();
        }

        if ($parsed_controller === null) {
            if ($this->defaultControllerClass === null) {
                return [null, null];
            }
            $parsed_controller = [$this->defaultControllerClass, $this->request->getActionMethod(),''];
        }

        $cObj = new $parsed_controller[0]($this->context, $this->request);
        $controller_method = $this->buildMethodName($parsed_controller[1]);

        $cObj->withControllerEndpoint($parsed_controller[2]);

        return [$cObj, $controller_method];
    }

    protected function exceptionToResponse(\Exception $e, $present_format): ?Response
	{
		// The method for child application to convert action exceptions to some non error responses
		// For example, on some exception we want to redirect the user to a different page instead to display the error
		return null;
	}

    private function getEndpoint()
    {
        $endpoint = $this->request->getEndpoint();

        if (empty($endpoint)) {
            return "/";
        }

        // this is the workaround for websites runing in some subfolder and endpoint is received with this subfolder
        if (strpos($endpoint, $this->context->config->applicationEndpointPrefix) === 0) {
            $endpoint = substr($endpoint, strlen($this->context->config->applicationEndpointPrefix));
        }

        if (strpos($endpoint, "/") !== 0) {
            $endpoint = "/" . $endpoint;
        }

        return $endpoint;
    }

    private function detectControllerFromIndex()
    {
        $endpoint = $this->getEndpoint();

        if (isset($this->endpointsIndex[$endpoint])) {
            return [$this->endpointsIndex[$endpoint][0], $this->endpointsIndex[$endpoint][1], ""];
        }

        foreach ($this->endpointsIndex as $key => $value) {
            if (substr($key, -1) === "*" && strpos($endpoint, substr($key, 0, -1)) === 0) {
                return [$value[0], $value[1], substr($endpoint, strlen($key) - 1)];
            }
            if (substr($key, -2) === "/*" && strpos($endpoint, substr($key, 0, -2)) === 0) {
                return [$value[0], $value[1], substr($endpoint, strlen($key) - 2)];
            }
        }

        return null;
    }
    /**
     * This method should be overloaded in a child class to detect controller from input data.
     * 
     * @return array|null [controller class, method name, endpoint] - endpoint is the part of the endpoint after the controller name
     */
    protected function detectControllerFromInput():array|null
    {
        return null;
    }

    private function buildMethodName($method_name = "")
    {
        $method = $this->request->getRequestMethod();

        if (empty($method_name) || !is_string($method_name)) {
            $method_name = "";
        }

        if ($method_name !== "") {
            $method_name = ucfirst(strtolower($method_name));
        }

        $method_prefixes = ['post', 'put', 'delete', 'head', 'options', 'get', 'patch', 'do'];

        if (in_array(strtolower($method), $method_prefixes)) {
            return strtolower($method) . $method_name;
        }
        return "get" . $method_name;
    }
        
    public function registerEndpoint($endpoint, $controller_class, $method_name = "")
    {
        if (is_array($endpoint)) {
            foreach ($endpoint as $e) {
                $this->endpointsIndex[$e] = [$controller_class, $method_name];
            }
        } else {
            $this->endpointsIndex[$endpoint] = [$controller_class, $method_name];
        }
    }
}