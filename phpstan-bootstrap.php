<?php

/*
 * Runs after Larastan has booted its Laravel app: register the admin UI's
 * views the way FoundationServiceProvider does, and each module's views under
 * its alias the way ModuleServiceProvider does (the modules are not booted
 * here), so view-string checks see them.
 */
if (function_exists('app') && app()->bound('view')) {
    app('view')->addLocation(__DIR__.'/ui/resources/views');

    foreach (glob(__DIR__.'/modules/*/module.json') ?: [] as $manifest) {
        $alias = json_decode((string) file_get_contents($manifest), true)['alias'] ?? null;
        $views = dirname($manifest).'/resources/views';

        if (is_string($alias) && is_dir($views)) {
            app('view')->addNamespace($alias, $views);
        }
    }
}
