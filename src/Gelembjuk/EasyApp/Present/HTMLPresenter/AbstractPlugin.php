<?php 

namespace Gelembjuk\EasyApp\Present\HTMLPresenter;

abstract class AbstractPlugin {
    protected \Gelembjuk\EasyApp\Context  $context;

    public function __construct(\Gelembjuk\EasyApp\Context $context)
    {
        $this->context = $context;
    }
}