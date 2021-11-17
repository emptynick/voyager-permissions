<?php

namespace Emptynick\Permissions\Traits;

use Carbon\Carbon;
use Spatie\Permission\Models\{Permission, Role};
use Spatie\Permission\Traits\HasRoles as SpatieHasRoles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Voyager\Admin\Facades\Voyager;

trait HasRoles {

    use SpatieHasRoles {
        SpatieHasRoles::permissions as permissions;
        SpatieHasRoles::roles as roles;
        SpatieHasRoles::hasPermissionTo as spatieHasPermissionTo;
    }

    public function hasPermissionTo($permission, string $guard = null): bool
    {
        $this->logRequest('permission', $permission, $guard);

        return true;
    }

    private function logRequest($type, $action, $guard): void
    {
        if (Cache::get('voyager-permissions-logging-enabled', false)) {
            $cache = Cache::get('voyager-permissions-cache', collect());
            if (!$cache instanceof Collection) {
                $cache = collect();
            }

            if ($cache->where('type', $type)->where('action', $action)->where('guard', $guard)->count() == 1) {
                $cache->transform(function ($item) use ($type, $action, $guard) {
                    if ($item['type'] == $type && $item['action'] == $action && $item['guard'] == $guard) {
                        $item['count'] += 1;
                        $item['time'] = Carbon::now();
                    }

                    return $item;
                });
            } else {
                $cache->push(collect([
                    'type'      => $type,
                    'action'    => $action,
                    'guard'     => $guard,
                    'time'      => Carbon::now(),
                    'count'     => 1,
                ]));
            }

            Cache::put('voyager-permissions-cache', $cache);
        }
    }
}