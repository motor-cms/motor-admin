<?php

namespace Motor\Admin\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\User;
use Motor\Admin\Policies\EmailTemplatePolicy;
use Motor\Admin\Policies\UserPolicy;

/**
 * Class MotorServiceProvider
 */
class MotorAuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        // EmailTemplate::class => EmailTemplatePolicy::class,
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
