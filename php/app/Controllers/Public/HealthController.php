<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class HealthController
{
    public function check(Request $request, array $params): void
    {
        try {
            Database::connection()->query('SELECT 1');
            Response::json(['status' => 'ok']);
        } catch (\Throwable) {
            Response::json(['status' => 'error'], 503);
        }
    }
}
