<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait BulkDestroysRecords
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  callable(Request): array<string, mixed>|null  $redirectParams
     * @param  callable(Model): bool|null  $deleter  Return true when a row was deleted; false to skip
     */
    protected function bulkDestroyRecords(
        Request $request,
        string $modelClass,
        string $redirectRoute,
        ?callable $redirectParams = null,
        int $max = 100,
        string $singularLabel = 'record',
        ?callable $deleter = null,
    ): RedirectResponse {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', "max:{$max}"],
            'ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        $models = $modelClass::query()->whereIn('id', $ids)->get();

        if ($models->isEmpty()) {
            return redirect()
                ->route($redirectRoute, $redirectParams ? $redirectParams($request) : [])
                ->with('warning', 'No matching records to delete.');
        }

        $deleted = 0;
        $skipped = 0;
        if ($deleter !== null) {
            foreach ($models as $model) {
                if ($deleter($model)) {
                    $deleted++;
                } else {
                    $skipped++;
                }
            }
        } else {
            $deleted = $modelClass::query()->whereIn('id', $models->pluck('id'))->delete();
        }

        $params = $redirectParams ? $redirectParams($request) : [];
        if ($deleted === 0) {
            return redirect()
                ->route($redirectRoute, $params)
                ->with('warning', $skipped > 0
                    ? 'No selected items could be deleted (they may be in use).'
                    : 'No records were deleted.');
        }

        $plural = $deleted === 1 ? $singularLabel : $singularLabel.'s';
        $msg = $deleted === 1
            ? "1 {$singularLabel} removed."
            : "{$deleted} {$plural} removed.";
        if ($skipped > 0) {
            $msg .= " {$skipped} skipped (in use or protected).";
        }

        return redirect()->route($redirectRoute, $params)->with('success', $msg);
    }
}
