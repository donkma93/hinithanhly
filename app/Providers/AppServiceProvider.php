<?php

namespace App\Providers;

use App\Repositories\CategoryRepository;
use App\Repositories\ConsignmentNoteRepository;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ConsignmentNoteRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\SupplierRepository;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(ConsignmentNoteRepositoryInterface::class, ConsignmentNoteRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (! in_array($event->command, ['db:wipe', 'migrate:fresh'], true)) {
                return;
            }

            if ($this->app->environment('testing') && ! config('branches.shared_database', false)) {
                return;
            }

            $prefix = (string) DB::getTablePrefix();
            $isSharedDatabase = (bool) config('branches.shared_database', false);

            if ($isSharedDatabase || $prefix !== '') {
                throw new RuntimeException(
                    "Lệnh [{$event->command}] bị chặn để bảo vệ database dùng chung giữa nhiều cơ sở (DB_PREFIX='{$prefix}'). "
                    .'Vui lòng chỉ dùng `php artisan migrate` hoặc backup trước khi thao tác thủ công.'
                );
            }
        });
    }
}
