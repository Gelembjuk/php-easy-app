<?php 

namespace Gelembjuk\WebApp;

class LinksManager {
    use ContextTrait;
    /**
     * Generate URL for a controller method. This should be overloaded in child classes if the format is different for urls.
     */
    public function makeUrl($controller = '', $method = '', $params = [])
    {
        $url = $this->context->getRelativeBaseURL();

        if ($controller != '') {
            $url .= $controller.'/';

            if ($method != '') {
                $url .= $method.'/';
            }
        }

        if (count($params) > 0) {
            $url .= '?'.http_build_query($params);
        }
        return $url;
    }
    public function makeAbsoluteUrl($controller = '', $method = '', $params = [])
    {
        $url = $this->makeUrl($controller, $method, $params);

        if (substr($this->context->getAbsoluteBaseURL(), -1) == '/') {
            if (substr($url, 0, 1) == '/') {
                $url = substr($url, 1);
            }
        }

        return $this->context->getAbsoluteBaseURL().$url;
    }
}