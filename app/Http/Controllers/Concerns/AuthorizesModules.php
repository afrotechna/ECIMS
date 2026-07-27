<?php

namespace App\Http\Controllers\Concerns;

trait AuthorizesModules
{
    protected function authorizeModule(string $module, string $action): void
    {
        $user = auth()->user();
        if (! $user || ! $user->canModule($module, $action)) {
            abort(403, 'You do not have permission to '.$action.' this area.');
        }
    }
}
