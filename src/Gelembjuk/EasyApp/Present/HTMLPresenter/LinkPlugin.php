<?php 

namespace Gelembjuk\EasyApp\Present\HTMLPresenter;

class LinkPlugin extends AbstractPlugin {
    public static $linkManObject = null;
    
    public function link($params, $smarty)
    {
        static $linksMan;

        if (!isset($linksMan)) {
            if (isset(self::$linkManObject)) {
                $linksMan = self::$linkManObject;
            } else {
                $linksMan = new \Gelembjuk\EasyApp\LinksManager($this->context);
            }
        }

        $controller = $params['controller'] ?? $params['c'] ?? '';
        unset($params['controller']);
        unset($params['c']);

        $method = $params['method'] ?? $params['m'] ?? '';
        unset($params['method']);
        unset($params['m']);

        $id = $params['id'] ?? '';
        unset($params['id']);

        if ($params['absolute'] ?? $params["a"] ?? '') {
            unset($params['absolute']);
            unset($params['a']);

            return $linksMan->makeAbsoluteLink($controller, $method, $id, $params);
        }

        return $linksMan->makeUrl($controller, $method, $id, $params);
    }
}
