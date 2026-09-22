<?php

declare(strict_types=1);

namespace Mrj\Foundation\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/** @api */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
