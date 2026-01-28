<?php

namespace Gelembjuk\EasyApp;

use \Gelembjuk\EasyApp\Request\AbstractRequest as Request;
use \Gelembjuk\EasyApp\Response\Response as Response;
use \Gelembjuk\EasyApp\Response\ErrorResponse as ErrorResponse;
use \Gelembjuk\EasyApp\Response\DataResponse as DataResponse;
use \Gelembjuk\EasyApp\Models\PublicModel;

use \Gelembjuk\EasyApp\Present\Builder as Builder;

abstract class Widget {
	use ContextTrait;

	protected Request $request;

	/**
	 * Format of data output. It can be set by widget. if not set it will be detected from the request
	 */
	protected $presentFormat = '';
	/**
	 * Default format of data output. It can be changed in child widgets. It is used when there is no other info
	 * about the format. For example, when the format is not set by the widget and not detected from the request
	 */
	protected $defaultPresentFormat = 'html';

	public function __construct(Context $context, Request|array $request = []) 
	{
		$this->withContext($context);
		
		$this->withRequest($request);
		
		$this->afterConstructor();
	}
	
	protected function afterConstructor() 
	{
		// this is for child classes to do some actions after constructor
	}
    public function withRequest(Request|array $request): static
    {
        if (is_array($request)) {
			$this->request = new Request($request);
		} else {
			$this->request = $request;
		}
        return $this;
    }
    public function getContent(): string
    {
        try {
            $data = $this->prepareData();

            if (is_string($data)) {
                return $data;
            }

            $response = new DataResponse($data);
        } catch (\Exception $e) {
            $response = new ErrorResponse("", $e);
        }

        return $this->buildContent($response);
    }

    protected function getTemplate(): string
    {
        // This method is used to get the template file name for the widget
        // It should be reloaded in the child class if the response is HTML
        return '';
    }

    protected function prepareData(): array|string|PublicModel
    {
        // This method is used to prepare the data for the widget
        // It can be reloaded in a child class to prepare the data (it is optional, only if there is no custom method)
        return [];
    }

    protected function buildContent(Response|array $response): string
    {
        // This method is used to build the content for the widget
        $presenterFormat = $this->decideOutputFormat();

        if (is_array($response)) {
            $response = new DataResponse($response);
        }

        if ($this->getTemplate() !== '' && $response instanceof DataResponse) {
            $response->withTemplate($this->getTemplate());
        }

		$presenter = Builder::createPresenter($this->context, $response, $presenterFormat);
        $presenter->buildOutput();
        return $presenter->getContent();
    }
    
	public function decideOutputFormat(): string
	{
		$forcedFormat = $this->context->getPresentFormat();

		if (!empty($forcedFormat)) {
			// It has been set on the application level
			return $forcedFormat;
		}

        if ($this->request->getPresentFormat() !== null) {
			// Input object requested this format
			return $this->request->getPresentFormat();
		}

		if (!empty($this->presentFormat)) {
			// Controller requested this format
			return $this->presentFormat;
		}

		$format = $this->decideOutputFormatFromInput();

		if (!empty($format)) {
			return $format;
		}

		return $this->defaultPresentFormat; // Default format
	}
	protected function decideOutputFormatFromInput(): string
	{
		return '';
	}
}
