<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function resolvePerPage(Request $request, int $default = 10): int
    {
        $allowedValues = [10, 20, 50, 100];
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, $allowedValues, true) ? $perPage : $default;
    }
}
