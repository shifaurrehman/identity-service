<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        DB::select('SELECT 1');

        return response()->json([
            'status' => 'ok',
            'checked_at' => now()->utc()->toISOString(),
        ]);
    }
}
