<?php

namespace Otomaties\VisualRentingDynamicsSync\Command\Contracts;

interface CommandContract
{
    public function handle(array $args, array $assocArgs): void;
}
