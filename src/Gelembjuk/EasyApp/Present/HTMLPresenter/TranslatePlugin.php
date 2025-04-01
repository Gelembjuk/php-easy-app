<?php 

namespace Gelembjuk\EasyApp\Present\HTMLPresenter;

class TranslatePlugin extends AbstractPlugin{
    
    public function translate($params, $smarty)
    {
        $key = $params['key'] ?? $params['k'] ?? '';
        $group = $params['group'] ?? $params['g'] ?? '';
        $list = [];

        if (isset($params['p1'])) {
            $list[] = $params['p1'];

            if (isset($params['p2'])) {
                $list[] = $params['p2'];

                if (isset($params['p3'])) {
                    $list[] = $params['p3'];

                    if (isset($params['p4'])) {
                        $list[] = $params['p4'];

                        if (isset($params['p5'])) {
                            $list[] = $params['p5'];
                        }
                    }
                }
            }
        }
        return $this->context->translation->getText($key, $group, ...$list);
    }
}