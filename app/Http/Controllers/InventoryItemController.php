<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryItemController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        $query = InventoryItem::query()->orderBy('name');

        if ($request->filled('search')) {
            $q = '%'.$request->search.'%';
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', $q)
                    ->orWhere('asset_tag', 'like', $q)
                    ->orWhere('serial_number', 'like', $q)
                    ->orWhere('category', 'like', $q)
                    ->orWhere('location', 'like', $q);
            });
        }
        if ($request->filled('kind')) {
            $query->where('kind', $request->kind);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->paginate(20)->withQueryString();
        $categories = InventoryItem::query()->distinct()->orderBy('category')->pluck('category')->filter();

        return view('inventory-items.index', compact('items', 'categories'));
    }

    public function create()
    {
        return view('inventory-items.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['created_by'] = auth()->id();

        InventoryItem::create($validated);

        return redirect()->route('inventory-items.index')->with('success', 'Inventory record added.');
    }

    public function edit(InventoryItem $inventory_item)
    {
        return view('inventory-items.edit', compact('inventory_item'));
    }

    public function update(Request $request, InventoryItem $inventory_item)
    {
        $inventory_item->update($this->validated($request, $inventory_item->id));

        return redirect()->route('inventory-items.index')->with('success', 'Inventory record updated.');
    }

    public function destroy(InventoryItem $inventory_item)
    {
        $inventory_item->delete();

        return redirect()->route('inventory-items.index')->with('success', 'Inventory record removed.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            InventoryItem::class,
            'inventory-items.index',
            fn (Request $r) => array_filter($r->only(['kind', 'status', 'q']), fn ($v) => $v !== null && $v !== ''),
            singularLabel: 'inventory record',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $assetRule = Rule::unique('inventory_items', 'asset_tag');
        if ($ignoreId !== null) {
            $assetRule = $assetRule->ignore($ignoreId);
        }

        $validated = $request->validate([
            'asset_tag' => ['nullable', 'string', 'max:64', $assetRule],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:100'],
            'kind' => ['required', 'string', Rule::in(array_keys(InventoryItem::KINDS))],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'unit' => ['nullable', 'string', 'max:32'],
            'location' => ['nullable', 'string', 'max:255'],
            'custodian' => ['nullable', 'string', 'max:150'],
            'acquired_on' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:128'],
            'condition' => ['required', 'string', Rule::in(array_keys(InventoryItem::CONDITIONS))],
            'status' => ['required', 'string', Rule::in(array_keys(InventoryItem::STATUSES))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (($validated['asset_tag'] ?? '') === '') {
            $validated['asset_tag'] = null;
        }
        foreach (['unit', 'location', 'custodian', 'supplier', 'serial_number', 'description', 'notes'] as $k) {
            if (isset($validated[$k]) && $validated[$k] === '') {
                $validated[$k] = null;
            }
        }
        if (empty($validated['acquired_on'])) {
            $validated['acquired_on'] = null;
        }
        if (! array_key_exists('cost', $validated) || $validated['cost'] === '' || $validated['cost'] === null) {
            $validated['cost'] = null;
        }

        return $validated;
    }
}
