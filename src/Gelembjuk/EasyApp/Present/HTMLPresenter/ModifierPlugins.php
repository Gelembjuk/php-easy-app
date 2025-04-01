<?php 

namespace Gelembjuk\EasyApp\Present\HTMLPresenter;

class ModifierPlugins extends AbstractPlugin {
    public function jsonencode($data)
    {
        if (empty($data)) {
            return '';
        }
        if (!is_array($data)) {
            return '';
        }
        return json_encode($data);
    }
    public function humandatetime($data)
    {
        if (empty($data)) {
            return '';
        }
        
        $dt = new \DateTime($data);
        
        return $dt->format('D, d M Y H:i');
    }
}
