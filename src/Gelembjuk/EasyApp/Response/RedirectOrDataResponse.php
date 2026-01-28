<?php

namespace Gelembjuk\EasyApp\Response;

use \Gelembjuk\EasyApp\Models\PublicModel;

class RedirectOrDataResponse extends DataResponse
{
    use RedirectResponseTrait;
    public function __construct(string $url, $message = "", array | PublicModel $data = [], string $template = "", $httpCode = 0)
    {
        parent::__construct($data, $template, null, $httpCode);
        $this->url = $url;
        $this->message = $message;
    }
}