<?php

namespace Motor\Admin\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Motor\Admin\Models\User;
use Motor\Admin\Policies\UserPolicy;

/**
 * Class MotorServiceProvider
 *
 * @package Motor\Admin\Providers
 */
class MotorAuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
