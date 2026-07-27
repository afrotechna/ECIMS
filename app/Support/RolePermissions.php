<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class RolePermissions
{
    public static function modules(): array
    {
        return config('permissions.modules', []);
    }

    public static function matrix(): array
    {
        return config('permissions.matrix', []);
    }

    public static function roleGrants(string $role): array
    {
        $role = User::normalizeRoleSlug($role);
        $matrix = self::matrix();

        return $matrix[$role] ?? [];
    }

    public static function allows(string $role, string $module, string $action): bool
    {
        $action = self::normalizeAction($action);
        $grants = self::roleGrants($role);

        if (isset($grants['*']) && self::actionListed($action, $grants['*'])) {
            return true;
        }

        $moduleActions = $grants[$module] ?? [];

        return self::actionListed($action, $moduleActions);
    }

    public static function canAny(string $role, string $action, array $modules): bool
    {
        foreach ($modules as $module) {
            if (self::allows($role, $module, $action)) {
                return true;
            }
        }

        return false;
    }

    public static function modulesWith(string $role, string $action): array
    {
        $out = [];
        foreach (array_keys(self::modules()) as $module) {
            if (self::allows($role, $module, $action)) {
                $out[] = $module;
            }
        }

        return $out;
    }

    /**
     * Resolve module + action for the current route (staff routes only).
     *
     * @return array{0: string, 1: string}|null
     */
    public static function resolveRoutePermission(Request $request): ?array
    {
        $name = $request->route()?->getName();
        if (! $name || ! is_string($name)) {
            return null;
        }

        if (str_ends_with($name, '.bulk-destroy')) {
            $prefix = explode('.', $name)[0];
            $module = config("permissions.route_prefixes.{$prefix}");
            if ($module) {
                return [$module, 'delete'];
            }
        }

        $overrides = config('permissions.route_overrides', []);
        if (isset($overrides[$name])) {
            return $overrides[$name];
        }

        $prefix = explode('.', $name)[0];
        $module = config("permissions.route_prefixes.{$prefix}");
        if (! $module) {
            return null;
        }

        return [$module, self::actionFromRequest($request)];
    }

    public static function actionFromRequest(Request $request): string
    {
        $name = $request->route()?->getName() ?? '';
        if (is_string($name)) {
            if (str_ends_with($name, '.create')) {
                return 'create';
            }
            if (str_ends_with($name, '.edit')) {
                return 'update';
            }
        }

        return match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };
    }

    private static function normalizeAction(string $action): string
    {
        return $action === 'destroy' ? 'delete' : $action;
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function actionListed(string $action, array $allowed): bool
    {
        return in_array('*', $allowed, true) || in_array($action, $allowed, true);
    }
}
