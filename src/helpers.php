<?php

declare(strict_types=1);

foreach (glob(__DIR__.'/Helpers/*.php') as $filename) {
    require_once $filename;
}
