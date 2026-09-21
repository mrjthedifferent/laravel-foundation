<?php

namespace Modules\ActivityLog\Traits;

use Mrj\Foundation\Traits\MyGuzzleClient;

trait IPTracker
{
    use MyGuzzleClient;

    private string $url = 'http://ip-api.com/json/';

    public function trackIP(string $ip)
    {
        $url = $this->url.$ip;

        return $this->guzzle_get_call($url, []);
    }
}
