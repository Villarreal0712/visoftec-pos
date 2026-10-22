<?php

namespace App\Providers;

use App\Models\Configuracion;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Proteger modelos en producción
        Model::preventLazyLoading(!$this->app->isProduction());

        // Forzar HTTPS en producción
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Paginación numerada personalizada
        Paginator::defaultView('vendor.pagination.custom');

        // Compartir configuración del comercio con todas las vistas
        View::composer('*', function ($view) {
            static $comercio = null;
            if ($comercio === null) {
                try {
                    $comercio = Configuracion::instancia();
                } catch (\Throwable) {
                    $comercio = new Configuracion(['nombre_comercio' => config('app.name')]);
                }
            }
            $view->with('comercio', $comercio);
        });

        // Binding explícito para el parámetro {rol} → Spatie Role
        Route::model('rol', Role::class);

        // Gate para super-admin
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Administrador') ? true : null;
        });
    }
}
