<?php

namespace Otomaties\VisualRentingDynamicsSync\Helpers;

class CachingPlugins
{
    public function clearPageCache()
    {
        // Super Page Cache
        do_action('swcfpc_purge_cache');

        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
    }
}
