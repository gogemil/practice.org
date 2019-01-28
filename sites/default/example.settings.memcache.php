<?php

// just tack this onto the settings.php somewhere
// either as a separate included file, or at the end of settings

/**
 * Memcache_Storage settings
 */

// Set’s default cache storage as Memcache and excludes database connection for cache
$settings['cache']['default'] = 'cache.backend.memcache_storage';
// Set’s Memcache key prefix for your site and useful in working sites with same memcache as backend.
$settings['memcache_storage']['key_prefix'] = 'ntca';
// Set’s Memcache storage server’s.
$settings['memcache_storage']['memcached_servers'] =  ['127.0.0.1:11211' => 'default'];

// Enables to display total hits and misses
$settings['memcache_storage']['debug'] = FALSE;

