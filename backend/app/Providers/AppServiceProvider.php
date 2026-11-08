<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Note: the resource/ and database/ folder remap now lives in
        // `bootstrap/app.php` (it must run before config is loaded).

        // Seeders and factories live in the top-level `database/` folder, outside the
        // Composer PSR-4 `App\` namespace. Register a small autoloader so that
        // `Database\Seeders\*` and `Database\Factories\*` resolve correctly for
        // `php artisan db:seed` / `migrate:fresh --seed`.
        spl_autoload_register(function (string $class) {
            foreach (['Seeders', 'Factories'] as $kind) {
                $prefix = "Database\\{$kind}\\";

                if (str_starts_with($class, $prefix)) {
                    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
                    $path = base_path('../database/'.strtolower($kind)."/{$relative}.php");

                    if (is_file($path)) {
                        require_once $path;
                    }

                    return;
                }
            }
        });
    }
}
