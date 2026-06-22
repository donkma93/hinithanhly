<?php

namespace Tests\Feature;

use App\Models\SystemErrorLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemErrorLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(DatabaseSeeder::class);

        Route::middleware('web')->get('/__test/system-error', function () {
            throw new \RuntimeException('Synthetic test exception');
        });
    }

    public function test_unhandled_exception_is_logged_and_rendered_with_generic_error_page(): void
    {
        config(['app.debug' => true]);

        $response = $this->get('/__test/system-error');

        $response->assertStatus(500);
        $response->assertSee('Ma loi tham chieu');
        $this->assertDatabaseCount('system_error_logs', 1);
        $this->assertSame(\RuntimeException::class, SystemErrorLog::query()->value('exception_class'));
    }

    public function test_admin_can_open_system_error_log_screen(): void
    {
        $user = User::query()->where('email', 'admin@kygui.local')->firstOrFail();
        $this->actingAs($user);

        SystemErrorLog::query()->create([
            'error_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'exception_class' => \RuntimeException::class,
            'message' => 'Stored for screen test',
            'file' => __FILE__,
            'line' => __LINE__,
            'status_code' => 500,
            'method' => 'GET',
            'url' => 'http://localhost/test',
            'route_name' => 'test.route',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'user_id' => $user->id,
            'trace' => 'trace',
            'context' => ['input' => []],
        ]);

        $response = $this->get(route('system-logs.index'));

        $response->assertOk();
        $response->assertSee('Log he thong');
        $response->assertSee('Stored for screen test');
    }
}
