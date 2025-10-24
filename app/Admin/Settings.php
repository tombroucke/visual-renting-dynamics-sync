<?php

namespace Otomaties\VisualRentingDynamicsSync\Admin;

class Settings
{
    const PREFIX = 'VISUAL_RENTING_DYNAMICS_';

    public function get(string $setting): ?string
    {
        return $this->findVariable($setting);
    }

    public function findVariable(string $optionName): ?string
    {
        $optionName = Settings::PREFIX.str_replace('-', '_', strtoupper($optionName));

        if (defined($optionName)) {
            return constant($optionName);
        }
        if (isset($_SERVER[$optionName])) {
            return $_SERVER[$optionName];
        }
        if (isset($_ENV[$optionName])) {
            return $_ENV[$optionName];
        }

        return null;
    }
}
