<?php

namespace Gelembjuk\EasyApp\Present;

use Smarty\Smarty;
use \Gelembjuk\EasyApp\Response\ErrorResponse as ErrorResponse;
use \Gelembjuk\EasyApp\Response\DataResponse as DataResponse;
use \Gelembjuk\EasyApp\Response\RedirectResponse as RedirectResponse;
use \Gelembjuk\EasyApp\Response\RedirectOrDataResponse as RedirectOrDataResponse;
use \Gelembjuk\EasyApp\Response\RedirectOrErrorResponse as RedirectOrErrorResponse;

class HTMLPresenter extends Presenter {
    const OUTPUT_FORMAT = "html";

    protected $format_identifier = self::OUTPUT_FORMAT;

    public static $extraPluginsRegisterCallback = null;

    public function withTemplatesPath(string $filePath)
    {
        $this->settings['templatepath'] = $filePath;
        return $this;
    }
    public function withTemplatesExtension(string $extension)
    {
        $this->settings['extension'] = $extension;
        return $this;
    }
    public function withBaseTemplateEnabled(string $contentPlaceholder)
    {
        $this->settings['support_base_template'] = true;
        $this->settings['base_template_content_variable'] = $contentPlaceholder;
        return $this;
    }

    public function present()
    {
        // In case of html response we do redirect for this kind of response
        if ($this->response instanceof RedirectOrDataResponse) {
            $this->redirect($this->response->getRedirectResponse());
            return;
        }

        // And for redirect or error we do redirect (for other presenters it would be processed as error)
        if ($this->response instanceof RedirectOrErrorResponse) {
            $this->redirect($this->response->getRedirectResponse());
            return;
        }

        return parent::present();
    }

    protected function redirect(RedirectResponse $response)
    {
        $this->setHttpCodeAndString(302, "302 Found");
        $this->appendHeader('Location', $response->getUrl());
    }

    protected function error(ErrorResponse $response)
    {
        if ($this->response->getHttpCode() == 0) {
            $this->response->withHttpCode(500);
        }
        $this->setHttpCodeAndString($response->getHttpCode(), "500 Internal Server Error");
        $this->appendHeader('Content-Type', 'text/html');
        
        // TODO. check if there is special template for errors. If yes, use it

        $data = '<html>';

        $data .= 'Error: '.$response->getMessage()."\n";

        if ($this->context->config->traceErrors) {
            if ($response->getException() !== null) {
                $data .= '<pre>';
                $data .= $response->getException()->getFile().' '.$response->getException()->getLine()."\n";
                foreach ($response->getException()->getTrace() as $trace) {
                    if (isset($trace['file'])) {
                        $data .= $trace['file'].' '.$trace['line']."\n";
                    } else if (isset($trace['class'])) {
                        $data .= $trace['class'].' '.$trace['type'].' '.$trace['function']."\n";
                    }
                    if (isset($trace['args'])) {
                        foreach ($trace['args'] as $arg) {
                            $data .= '  '.substr(print_r($arg, true),0,200)."\n";
                        }
                    }
                }
                $data .= '</pre>';
            }
        }
        $data .= '</html>';

        $this->data = $data;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }

    protected function data(DataResponse $response)
    {
        // This uses Jinja2 to render the template based on the data in the response
        if ($response->getHttpCode() == 0) {
            $response->withHttpCode(200);
        }

        $this->setHttpCodeAndString($response->getHttpCode(), "200 Ok");
        $this->appendHeader('Content-Type', 'text/html');

        if ($response->hasCompleteResponse() && !$response->hasBaseTemplate()) {
            // return as is the complete response
            // but if there is external data continue
            $this->data = $response->getCompleteResponse();
            $this->dataType = self::DATA_TYPE_STRING;
            return ;
        }

        $smarty = new Smarty();

        $templatesPath = $this->settings['templatepath'] ?? '';
        $compiledPath = $this->settings['compiledpath'] ?? '';

        $smarty->setTemplateDir($templatesPath);
		$smarty->setCompileDir($compiledPath);

        $this->configureSmartyPlugins($smarty);

        if (isset(self::$extraPluginsRegisterCallback)) {
            /**
             * Load more plugins if needed. It should be set from outside. This are custom plugins
             */
            call_user_func(self::$extraPluginsRegisterCallback, $smarty);
        }
        $extension = '.'.($this->settings["extension"] ?? 'htm');

        // copy each key from the $this->response->getData() to the template
        foreach ($response->getData() as $key => $value) {
            $smarty->assign($key, $value);
        }

		if ($response->hasCompleteResponse()) {
            $htmlString = $response->getCompleteResponse();
        } else {
            $templatePath = $response->getTemplate();

            if (empty($templatePath)) {
                throw new \Exception('Template is not set in the response object');
            }
            
            $templatePath .= $extension;
            
            $htmlString = $smarty->fetch($templatePath);
        }

        if ($this->settings['support_base_template'] ?? false) {
            /**
             * If there is a base template, we use it to wrap the content.
             * But we will do it only if the response has a base template set
             */
            if ($response->hasBaseTemplate()) {
                $baseTemplatePath = $response->getBaseTemplate().$extension;
                $smarty->assign($this->settings['base_template_content_variable'], $htmlString);
                $htmlString = $smarty->fetch($baseTemplatePath);
            }
        }

        $this->data = $htmlString;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }
    private function configureSmartyPlugins($smarty)
    {
        if (is_array($this->settings['enable_plugins'])) {
            if (in_array('translate', $this->settings['enable_plugins'])) {
                $translatePlugin = new HTMLPresenter\TranslatePlugin($this->context);
                $smarty->registerPlugin('function', 't', [$translatePlugin, 'translate']);
            }
            if (in_array('link', $this->settings['enable_plugins'])) {
                $linksPlugin = new HTMLPresenter\LinkPlugin($this->context);
                $smarty->registerPlugin('function', 'link', [$linksPlugin, 'link']);
            }

            if (in_array('pagination', $this->settings['enable_plugins'])) {
                $paginationPlugin = new HTMLPresenter\PaginationPlugin($this->context);
                $smarty->registerPlugin('function', 'pagination', [$paginationPlugin, 'pagination']);
            }
            
            if (in_array('jsonencode', $this->settings['enable_plugins'])) {
                $modifiersPlugin = new HTMLPresenter\ModifierPlugins($this->context);
                $smarty->registerPlugin('modifier', 'jsonencode', [$modifiersPlugin, 'jsonencode']);
            }
            if (in_array('humandatetime', $this->settings['enable_plugins'])) {
                $modifiersPlugin = new HTMLPresenter\ModifierPlugins($this->context);
                $smarty->registerPlugin('modifier', 'humandatetime', [$modifiersPlugin, 'humandatetime']);
            }
        }
    }
}