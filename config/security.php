<?php

$trustedHosts = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_HOSTS', '')))));
$trustedProxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));

return [
    'trusted_hosts' => array_map(function ($host) {
        return '^'.preg_quote($host, '/').'$';
    }, $trustedHosts),

    // Kosong berarti aplikasi tidak mempercayai forwarded headers dari proxy mana pun.
    // Jangan gunakan "*"; cantumkan IP reverse proxy/load balancer secara eksplisit.
    'trusted_proxies' => $trustedProxies,
];
