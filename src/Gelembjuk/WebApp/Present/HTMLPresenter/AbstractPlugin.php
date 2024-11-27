<?php 

namespace Gelembjuk\WebApp\Present\HTMLPresenter;

abstract class AbstractPlugin {
    protected \Gelembjuk\WebApp\Context  $context;

    public function __construct(\Gelembjuk\WebApp\Context $context)
    {
        $this->context = $context;
    }
}