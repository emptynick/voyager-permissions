<?php

namespace Emptynick\Permissions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, Route};
use Inertia\Inertia;
use Spatie\Permission\Models\{Permission, Role};
use Voyager\Admin\Classes\MenuItem;
use Voyager\Admin\Contracts\Plugins\AuthorizationPlugin;
use Voyager\Admin\Contracts\Plugins\Features\Provider\{JS, MenuItems, ProtectedRoutes};
use Voyager\Admin\Facades\Voyager;
use Voyager\Admin\Manager\Menu;

class Permissions implements AuthorizationPlugin, JS, MenuItems, ProtectedRoutes
{
    public $name = 'Voyager Permissions';
    public $description = 'Permission system for Voyager II using spatie/laravel-permission';
    public $repository = 'emptynick/voyager-permissions';
    public $website = 'https://github.com/emptynick/voyager-permissions';
    public $version = '1.0.0';


    public function __construct()
    {
        $this->readme = realpath(dirname(__DIR__, 1).'/README.md');
    }

    public function authorize($user, $ability, $arguments = []): ?bool
    {
        // Super user
        if ($user->hasPermissionTo('*')) {
            return true;
        }

        // No permission to browse Voyager at all
        if (!$user->hasPermissionTo('browse voyager')) {
            return false;
        }
        if (count($arguments) >= 1) {
            if ($arguments[0] instanceof \Illuminate\Database\Eloquent\Model) {
                return $user->hasAnyPermission([
                    $ability." bread ".$arguments[0]->getTableName(),
                    $ability." bread *",
                    "* bread *",
                    "* bread ".$arguments[0]->getTableName()
                ]);
            } elseif ($arguments[0] instanceof \Voyager\Admin\Classes\Bread) {
                if ($ability == 'browse') {
                    return $user->hasPermissionTo('browse builder');
                } elseif ($ability == 'add' && is_string($arguments[1])) {
                    return $user->hasAnyPermission(['add builder *', 'add builder '.$arguments[1]]);
                } else {
                    return $user->hasAnyPermission([
                        $ability." builder ".$arguments[0]->table,
                        $ability." builder *",
                        "* builder *",
                        "* builder ".$arguments[0]->table
                    ]);
                }
            } elseif (is_string($arguments[0])) {
                return $user->hasPermissionTo($ability.' '.$arguments[0]);
            }
        }

        return true;
    }

    public function filterLayouts($bread, $action, $layouts): Collection
    {
        return $layouts;
    }

    public function provideJS() : string
    {
        return file_get_contents(realpath(dirname(__DIR__, 1).'/dist/voyager-permissions.umd.js'));
    }

    public function provideMenuItems(Menu $menuManager): void
    {
        $menuManager->addItems((new MenuItem('Permissions', 'lock-closed'))->route('voyager.voyager-permissions'));
    }

    public function provideProtectedRoutes(): void
    {
        Route::get('permissions', function () {
            Voyager::resolvePermissions(['browse', 'read', 'edit' => [1, 2, 3]]);
            return Inertia::render('voyager-permissions')->withViewData('title', 'Permission manager');
        })->name('voyager-permissions');

        Route::post('permissions', function (Request $request) {
            $selected_type = $request->input('selected.type', null);
            $selected_id = $request->input('selected.id', null);

            return [
                'permissions'   => $this->getPermissions($request, $selected_type, $selected_id),
                'roles'         => $this->getRoles($request, $selected_type, $selected_id),
                'users'         => $this->getUsers($request, $selected_type, $selected_id),
                'logging'       => Cache::get('voyager-permissions-logging-enabled', false),
            ];
        })->name('voyager-permissions');

        Route::post('permissions-assign', function (Request $request) {
            $selected_type = $request->input('selected_type', null);
            $selected_id = $request->input('selected_id', null);
            $assign_type = $request->input('assign_type', null);
            $assign_ids = $request->input('assign_ids', null);

            if ($selected_type == 'permission') {
                $permission = Permission::findOrFail($selected_id);
                if ($assign_type == 'roles') {
                    $permission->roles()->sync($assign_ids);
                } elseif ($assign_type == 'users') {
                    $permission->users()->sync($assign_ids);
                }
            } elseif ($selected_type == 'role') {
                $role = Role::findOrFail($selected_id);
                if ($assign_type == 'permissions') {
                    $role->permissions()->sync($assign_ids);
                } elseif ($assign_type == 'users') {
                    $role->users()->sync($assign_ids);
                }
            } elseif ($selected_type == 'user') {
                $user = Voyager::auth()->user()->findOrFail($selected_id);
                if ($assign_type == 'permissions') {
                    $user->permissions()->sync($assign_ids);
                } elseif ($assign_type == 'roles') {
                    $user->roles()->sync($assign_ids);
                }
            }
        })->name('voyager-permissions-assign');

        Route::post('permissions/add-permission', function (Request $request) {
            if (Permission::where('name', $request->input('name'))->count() == 0) {
                Permission::create([
                    'name' => $request->input('name'),
                ]);

                return abort(200);
            }

            return response()->json(['message' => 'Permission with name "'.$request->input('name').'" already exists!'], 500);
        })->name('voyager-permissions.add-permission');

        Route::post('permissions/add-role', function (Request $request) {
            if (Role::where('name', $request->input('name'))->count() == 0) {
                Role::create([
                    'name' => $request->input('name'),
                ]);

                return abort(200);
            }

            return response()->json(['message' => 'Role with name "'.$request->input('name').'" already exists!'], 500);
        })->name('voyager-permissions.add-role');

        Route::post('permissions/toggle-logging', function (Request $request) {
            Cache::put('voyager-permissions-logging-enabled', $request->input('enabled'));

            return response(200);
        })->name('voyager-permissions.toggle-logging');

        Route::post('permissions/get-logs', function () {
            return Cache::get('voyager-permissions-cache', collect());
        })->name('voyager-permissions.get-logs');

        Route::post('permissions/clear-logs', function () {
            return Cache::put('voyager-permissions-cache', collect());
        })->name('voyager-permissions.clear-logs');

        Route::post('permissions/remove', function (Request $request) {
            $type = $request->input('type', null);
            $id = $request->input('id', null);
            if ($type == 'permission') {
                return Permission::destroy($id);
            } elseif ($type == 'role') {
                return Role::destroy($id);
            }

            return abort(500);
        })->name('voyager-permissions.remove');
    }

    private function getPermissions(Request $request, mixed $selected_type, mixed $selected_id): LengthAwarePaginator
    {
        return Permission::where(function ($query) use ($selected_type, $selected_id) {
                if ($selected_type == 'user') {
                    // Only show permissions assigned to the user
                    return $query->whereIn('id', Voyager::auth()->user()->findOrFail($selected_id)->permissions()->pluck('id'));
                } else if ($selected_type == 'role') {
                    // Only show permissions in the selected role
                    return $query->whereIn('id', Role::findOrFail($selected_id)->permissions()->pluck('id'));
                }

                return $query;
            })
            ->where('name', 'LIKE', '%'.$request->get('permissions_query', '').'%')
            ->with(['roles', 'users' => function ($query) {
                return $query->select(Voyager::auth()->user()->getKeyName().' AS id');
            }])
            ->orderBy('name')
            ->paginate($request->get('per_page', 5), ['id', 'name'], 'permissions_page');
    }

    private function getRoles(Request $request, mixed $selected_type, mixed $selected_id): LengthAwarePaginator
    {
        return Role::where(function ($query) use ($selected_type, $selected_id) {
                if ($selected_type == 'user') {
                    // Only show roles assigned to the user
                    return $query->whereIn('id', Voyager::auth()->user()->findOrFail($selected_id)->roles()->pluck('id'));
                } else if ($selected_type == 'permission') {
                    // Only show roles that contain the selected permission
                    return $query->whereIn('id', Permission::findOrFail($selected_id)->roles()->pluck('id'));
                }

                return $query;
            })
            ->where('name', 'LIKE', '%'.$request->get('permissions_query', '').'%')
            ->with(['permissions', 'users' => function ($query) {
                return $query->select(Voyager::auth()->user()->getKeyName().' AS id');
            }])
            ->orderBy('name')
            ->paginate($request->get('per_page', 5), ['id', 'name'], 'permissions_page');
    }

    private function getUsers(Request $request, mixed $selected_type, mixed $selected_id): LengthAwarePaginator
    {
        $auth = Voyager::auth();
        $model = $auth->user();

        return $model
            ->where(function ($query) use ($selected_type, $selected_id) {
                if ($selected_type == 'permission') {
                    return $query->whereHas('permissions', function ($query) use ($selected_id) {
                        return $query->where('id', $selected_id);
                    });
                } elseif ($selected_type == 'role') {
                    return $query->whereHas('roles', function ($query) use ($selected_id) {
                        return $query->where('id', $selected_id);
                    });
                }

                return $query;
            })
            ->where($auth->nameField(), 'LIKE', '%'.$request->get('users_query', '').'%')
            ->with(['permissions', 'roles'])
            ->orderBy($auth->nameField())
            ->paginate($request->get('per_page', 5), [$auth->nameField().' AS name', $model->getKeyName().' AS id'], 'users_page');
    }
}
