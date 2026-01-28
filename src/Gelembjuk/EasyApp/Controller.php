<?php

namespace Gelembjuk\EasyApp;

use \Gelembjuk\EasyApp\Request\AbstractRequest as Request;
use \Gelembjuk\EasyApp\Response\Response as Response;
use \Gelembjuk\EasyApp\Response\ErrorResponse as ErrorResponse;
use \Gelembjuk\EasyApp\Response\DataResponse as DataResponse;
use \Gelembjuk\EasyApp\Response\NoContentResponse as NoContentResponse;
use \Gelembjuk\EasyApp\Response\RedirectResponse as RedirectResponse;

use \Gelembjuk\EasyApp\Present\Builder as Builder;
use \Gelembjuk\EasyApp\Present\Presenter as Presenter;
use \Gelembjuk\EasyApp\Present\HTMLPresenter as HTMLPresenter;

use \Gelembjuk\EasyApp\Exceptions\NotFoundException as NotFoundException;

class Controller {
	use ContextTrait;

	const NON_WEB_REQUEST_METHOD_DO = 'DO';

	protected Request $request;

	/**
	 * Format of data output. It can be set by controller. if not set it will be detected from the request
	 */
	protected $presentFormat = '';
	/**
	 * Default format of data output. It can be changed in child controllers. It is used when there is no other info
	 * about the format. For example, when the format is not set by the controller and not detected from the request
	 */
	protected $defaultPresentFormat = 'html';

	/**
	 * Part of the endpoint that is left after the controller name and method was detected
     * Example, for /auth/login/USERID it would be USERID because auth is the controller name and login is the method name
	 * Or /download/FolderA/FolderB/Filename.docx it would be FolderA/FolderB/Filename.docx (depending on rules and endpoints format)
	 */
	protected $controllerEndpoint = '';

	/**
	 * The url where a user should be redirected in case of error during an action
	 * This will be used only if there is no custom url in an exception object
	 */
	protected $redirectUrlInCaseOfError = null;
	/**
	 * Extra headers to attach to the response
	 */
	protected array $extraHeaders = [];
	/**
	 * 
	 */
	protected array $cookies = [];

	public function __construct(Context $context, Request|array $request) 
	{
		$this->withContext($context);
		
		if (is_array($request)) {
			$request = new Request($request);
		} else {
			$this->request = $request;
		}
		
		$this->afterConstructor();
	}
	
	protected function afterConstructor() 
	{
		// this is for child classes to do some actions after constructor
	}

	public function withControllerEndpoint(string $endpoint): Controller
	{
		$this->controllerEndpoint = $endpoint;
		return $this;
	}

	protected function beforeAction() 
	{
		// This method is called before the action method
		// It can be used in controller to execute something before the action
		// for example, to check permissions, user session etc.
		// It is executed before any action in the controller
	}

	protected function beforeGetAction() 
	{
		// Additional call after beforeAction for GET only
	}

	protected function beforePostAction() 
	{
		// Additional call after beforeAction for POST only
	}

	protected function beforePutAction() 
	{
		// Additional call after beforeAction for PUT only
	}

	protected function beforeDeleteAction() 
	{
		// Additional call after beforeAction for DELETE only
	}

	protected function beforeHeadAction() 
	{
		// Additional call after beforeAction for HEAD only
	}

	protected function beforeOptionsAction() 
	{
		// Additional call after beforeAction for OPTIONS only
	}

	protected function beforePatchAction() 
	{
		// Additional call after beforeAction for PATCH only
	}

	protected function beforeDoAction() 
	{
		// Additional call after beforeAction for PATCH only
	}

	protected function beforePresenting(Response $response, $presenterFormat): Response 
	{
		// This method is called before the response is presented
		// It can be used in controller to execute something before the response is presented
		// for example, to add some extra data to the response to display it in the view
		// It is executed before the response is presented

		// Example, if a format is HTML and the response is Data this is good place 
		// to add some extra info like title, description for a page, some common info to be displayed on every page.
		return $response;
	}

	protected function exceptionToResponse(\Exception $e): ?Response
	{
		// The method for child controllers to convert action exceptions to some non error responses
		// For example, on some exception we want to redirect the user to a different page instead to display the error
		return null;
	}
    
	public function action(string $methodName, array $input_arguments = []): Presenter
	{
		$response = null;
		$methodExists = false;

		try {
			if (count($input_arguments) > 0) {
				$this->request->setPriorityData($input_arguments);
			}

			if (!method_exists($this, $methodName)) {
				$this->context->errorLogger->error("Method not found for the endpoint - ".self::class."::$methodName");
				if ($this->context->config->traceErrors) {
					throw new NotFoundException("Method not found for the endpoint. ".$this->getOwnName()."::$methodName");
				}
				throw new NotFoundException("Method not found for the endpoint");
			}

			$this->beforeAction();

			$httpMethod = strtolower($this->request->getRequestMethod());

			switch ($httpMethod) {
				case 'get':
					$this->beforeGetAction();
					break;
				case 'post':
					$this->beforePostAction();
					break;
				case 'put':
					$this->beforePutAction();
					break;
				case 'delete':
					$this->beforeDeleteAction();
					break;
				case 'head':
					$this->beforeHeadAction();
					break;
				case 'options':
					$this->beforeOptionsAction();
					break;
				case 'patch':
					$this->beforePatchAction();
					break;
				case strtolower(self::NON_WEB_REQUEST_METHOD_DO):
					$this->beforeDoAction();
					break;
			}
			// Here we prepare the arguments for the method. It is optional to have arguments in the method
			// For now we support only some basic types of arguments
			$args = [];

			$r = new \ReflectionMethod($this, $methodName);
			$params = $r->getParameters();
			
			foreach ($params as $param) {
				//$param is an instance of ReflectionParameter
				$type = !$param->hasType() ? 'string' : $param->getType()->getName();
				
				$args[$param->getName()] = $this->request->get($param->getName(),$type, $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
				
				// TODO. Check if the parameter is required and throw an exception if it is not provided
			}
			// actual call of the method from the controller
			$response = $this->$methodName(...$args);

			if (!($response instanceof Response)) {
				$response = $this->wrapCustomResponse($response);
			}
		} catch (\Exception $e) {
			$response = new ErrorResponse("", $e);
			
			$customResponse = $this->exceptionToResponse($e);

			$response = $customResponse ?? $response;
		}

		$presenterFormat = $this->decideOutputFormat($response);

		if ($response instanceof ErrorResponse) {
			if ($response->getException() !== null && $response->getHttpCode() == 0) {
				// if there is an exception and no http code, we set it here
				$response = $this->completeError($response);
			}

			if (!empty($this->redirectUrlInCaseOfError) && $presenterFormat == HTMLPresenter::OUTPUT_FORMAT) {
				// we use redirect response instead of error response
				$response = new RedirectResponse($this->redirectUrlInCaseOfError, $response->getMessage());
			}
		}

		$response = $this->beforePresenting($response, $presenterFormat);

		foreach ($this->extraHeaders as $headerKey => $header) {
			$response->withHeader($headerKey, $header);
		}

		$presenter = Builder::createPresenter($this->context, $response, $presenterFormat);

		$presenter->buildOutput();

		return $presenter;
	}
	protected function completeError(ErrorResponse $response): ErrorResponse
	{
		/**
		 * This is for child classes. To assign some http codes to the error response
		 */

		/**
		 * This is just an example. We can assign some http codes to the error response
		if ($response->getException() instanceof NotFoundException) {
			return $response->withHttpCode(404);
		}
		*/
		 
		return $response;
	}
        
    protected function wrapCustomResponse($response): Response
	{
		/**
		 * Protected method allows to simplify the controller methods response format
		 * Controller can implement this method to wrap the response into the custom object
		 * By default it will try to convert some common response types to the Response object
		 */
		if (is_array($response)) {
			return new DataResponse($response);
		}
		if (is_string($response)) {
			return new DataResponse([], '', $response);
		}
		if ($response instanceof \Gelembjuk\EasyApp\Models\PublicModel) {
			return new DataResponse($response);
		}
		if ($response === null) {
			return new NoContentResponse();
		} 
		// else just return the response as is inside DataResponse
		return new DataResponse(['status' => strval($response)]);
	}

	public function decideOutputFormat(?Response $response = null): string
	{
		// this is highest priority. 
		$forcedFormat = $this->context->getPresentFormat();

		if (!empty($forcedFormat)) {
			// It has been set on the application level
			return $forcedFormat;
		}
		// this is second priority. it is set by the controller
		if (!empty($this->presentFormat)) {
			// Controller requested this format
			return $this->presentFormat;
		}
		if ($response !== null && $response->hasPresenter()) {
			// Controller has set it with the response. It is same priority as above
			// both are set by controller
			return $response->getPresenter();
		}
		// this is the thirt priority. It is first check of user's preferred format
		if ($this->request->getPresentFormat() !== null) {
			// Input requested this format. it can be set in controller too
			return $this->request->getPresentFormat();
		}
		// this is the second check of users preferred format
		// this is calculated on Action level
		$format = $this->decideOutputFormatFromInput();

		if (!empty($format)) {
			return $format;
		}

		return $this->defaultPresentFormat; // Default format
	}
	protected function decideOutputFormatFromInput(): string
	{
		// This is a place to decide the output format based on the request
		// For example, if the request is for JSON, XML, HTML, etc.
		// It can be based on the request headers, request parameters, etc.
		// It can be reloaded in a child class to define the logic
		return '';
	}
	protected function appendCookies($cookies)
	{
		$this->cookies = array_merge($this->cookies, $cookies);
	}
	protected function getOwnName(): string
	{
		$reflect = new \ReflectionClass($this);
		return $reflect->getShortName();
	}
}
