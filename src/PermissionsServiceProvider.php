<?php

namespace Emptynick\Permissions;

use Illuminate\Support\ServiceProvider;
use Voyager\Admin\Manager\Plugins as PluginManager;

class PermissionsServiceProvider extends ServiceProvider
{
    public function boot(PluginManager $pluginmanager)
    {
        $pluginmanager->addPlugin(\Emptynick\Permissions\Permissions::class);
    }

    public function register()
    {
        //
    }
}