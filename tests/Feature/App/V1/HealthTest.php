<?php

use Illuminate\Support\Facades\DB;
use function Pest\Laravel\getJson;


test('health endpoint returns ok when database query succeeds', function () {
    DB::shouldReceive('select')
    ->once()
    ->with('SELECT 1')
    ->andReturn([(object) ['1' => 1]]);
    $response = getJson('/api/v1/health');
    $response
    ->assertOk()
    ->assertJsonPath('status', 'ok')
    ->assertJsonStructure([
        'status',
        'checked_at',
    ]);
    expect(strtotime($response->json('checked_at')))->not()->toBeFalse();
});
