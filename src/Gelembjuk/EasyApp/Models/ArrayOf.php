<?php

namespace Gelembjuk\EasyApp\Models;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ArrayOf
{
    public function __construct(public string $class) {}
}