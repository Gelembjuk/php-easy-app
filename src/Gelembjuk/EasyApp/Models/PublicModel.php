<?php

namespace Gelembjuk\EasyApp\Models;

/**
 * Base class for public-facing models.
 * 
 * Inherits from InternalModel and is intended for models that are exposed
 * to external systems or APIs.
 */

abstract class PublicModel extends InternalModel
{
    /**
     * @var string|null Template name associated with this public model.
     */
    protected ?string $template = null;

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function withTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }
}