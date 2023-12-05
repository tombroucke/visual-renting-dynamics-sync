<?php

namespace Otomaties\VisualRentingDynamicsSync\Services\Contracts;

interface Runnable
{
    public function run(array $args, array $assocArgs): void;
}
