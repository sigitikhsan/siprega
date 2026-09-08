<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array
     */
    public function hosts()
    {
        $configuredHosts = config('security.trusted_hosts', []);

        return $configuredHosts ?: [$this->allSubdomainsOfApplicationUrl()];
    }
}
