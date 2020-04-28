<?php

namespace Gofish\Datatable;

use Illuminate\Support\ServiceProvider;

class DatatableServiceProvider extends ServiceProvider {

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        request()->macro('hasFilled', function (array $keys) {
            foreach ($keys as $key) {
                if (!$this->filled($key)) {
                    return false;
                }
            }

            return true;
        });
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
    }
}
