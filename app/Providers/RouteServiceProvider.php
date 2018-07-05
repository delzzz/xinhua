<?php

namespace App\Providers;

//use Illuminate\Support\Facades\Route;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';
    protected $backendNamespace;
    protected $frontendNamespace;
    //protected $apiNamespace;
    protected $currentDomain;

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $router = app('router');
        //
        $this->backendNamespace = 'App\Http\Controllers\Backend';
        $this->frontendNamespace = 'App\Http\Controllers\Frontend';
        //$this->apiNamespace = 'App\Http\Controllers\API';
        $this->currentDomain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "";
        parent::boot($router);

        //parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
//    public function map()
//    {
//        $this->mapApiRoutes();
//
//        $this->mapWebRoutes();
//
//        //
//    }
    public function map(Router $router)
    {
        //$this->mapApiRoutes();
        //$this->mapWebRoutes();

        $backendUrl = config('route.backend_url');
        $frontendUrl = config('route.frontend_url');
        //$apiUrl = config('route.api_url');

        switch ($this->currentDomain) {
//            case $apiUrl:
//                // API路由
//                $router->group([
//                    'domain' => $apiUrl,
//                    'namespace' => $this->apiNamespace],
//                    function ($router) {
//                        require app_path('Http/routes-api.php');
//                    }
//                );

//                break;
            case $backendUrl:
                // 后端路由
                $router->group([
                    'domain' => $backendUrl,
                    'namespace' => $this->backendNamespace],
                    function ($router) {
                        require app_path('Http/routes-backend.php');
                    }
                );
                break;
            default:
                // 前端路由
                $router->group([
                    'domain' => $frontendUrl,
                    'namespace' => $this->frontendNamespace],
                    function ($router) {
                        require app_path('Http/routes-frontend.php');
                    }
                );
                break;
        }

    }
    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
