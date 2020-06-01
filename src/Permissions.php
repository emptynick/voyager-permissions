<?php

namespace Emptynick\Permissions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Voyager\Admin\Classes\Formfield;
use Voyager\Admin\Contracts\Plugins\AuthorizationPlugin;

class Permissions implements AuthorizationPlugin
{
    public $name = 'Voyager Permissions';
    public $description = 'Permission system for Voyager II using spatie/laravel-permission';
    public $repository = 'emptynick/voyager-permissions';
    public $website = 'https://github.com/emptynick/voyager-permissions';
    public $version = '1.0.0';

    public function getInstructionsView(): ?View
    {
        return null;
    }

    public function registerProtectedRoutes()
    {
        //
    }

    public function registerPublicRoutes()
    {
        Route::get('permissions', function () {
            return $this->getSettingsView();
        })->name('voyager-permissions');
    }

    public function getSettingsView(): ?View
    {
        return null;
    }

    public function getCssRoutes(): array
    {
        return [];
    }

    public function getJsRoutes(): array
    {
        return [];
    }
}
