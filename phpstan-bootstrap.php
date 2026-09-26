<?php

/*
 * Runs after Larastan has booted its Laravel app: register the admin UI's
 * views the way FoundationServiceProvider does, so view-string checks see them.
 */
if (function_exists('app') && app()->bound('view')) {
    app('view')->addLocation(__DIR__.'/ui/resources/views');
}
