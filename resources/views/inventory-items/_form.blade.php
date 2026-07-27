@php
    $inv = $inventory_item ?? null;
@endphp
<form action="{{ $action }}" method="POST">
    @csrf
    @if(strtoupper($method) === 'PUT')
        @method('PUT')
    @endif
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Asset / inventory tag</label>
            <input type="text" name="asset_tag" class="form-control @error('asset_tag') is-invalid @enderror" value="{{ old('asset_tag', $inv?->asset_tag) }}" maxlength="64" placeholder="Optional unique number">
            @error('asset_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $inv?->name) }}" required maxlength="255">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Kind <span class="text-danger">*</span></label>
            <select name="kind" class="form-select @error('kind') is-invalid @enderror" required>
                @foreach(\App\Models\InventoryItem::KINDS as $k => $label)
                    <option value="{{ $k }}" {{ old('kind', $inv?->kind ?? 'asset') === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('kind')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category', $inv?->category ?? 'General') }}" required maxlength="100" list="inventory-categories">
            <datalist id="inventory-categories">
                <option value="General">
                <option value="IT / ICT">
                <option value="Laboratory">
                <option value="Clinical / Medical">
                <option value="Furniture">
                <option value="Office supplies">
                <option value="Library">
                <option value="Vehicle">
                <option value="Tools / Workshop">
                <option value="Kitchen / Catering">
            </datalist>
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Quantity <span class="text-danger">*</span></label>
            <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $inv?->quantity ?? 1) }}" min="1" required>
            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Unit</label>
            <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $inv?->unit) }}" maxlength="32" placeholder="ea, box…">
            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $inv?->location) }}" maxlength="255" placeholder="Building, room, store…">
            @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Custodian</label>
            <input type="text" name="custodian" class="form-control @error('custodian') is-invalid @enderror" value="{{ old('custodian', $inv?->custodian) }}" maxlength="150" placeholder="Person or office">
            @error('custodian')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Serial number</label>
            <input type="text" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror" value="{{ old('serial_number', $inv?->serial_number) }}" maxlength="128">
            @error('serial_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Acquired on</label>
            <input type="date" name="acquired_on" class="form-control @error('acquired_on') is-invalid @enderror" value="{{ old('acquired_on', $inv?->acquired_on?->format('Y-m-d')) }}">
            @error('acquired_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Cost (TZS)</label>
            <input type="number" name="cost" step="0.01" min="0" class="form-control @error('cost') is-invalid @enderror" value="{{ old('cost', $inv?->cost) }}" placeholder="Optional">
            @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Supplier</label>
            <input type="text" name="supplier" class="form-control @error('supplier') is-invalid @enderror" value="{{ old('supplier', $inv?->supplier) }}" maxlength="255">
            @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Condition <span class="text-danger">*</span></label>
            <select name="condition" class="form-select @error('condition') is-invalid @enderror" required>
                @foreach(\App\Models\InventoryItem::CONDITIONS as $k => $label)
                    <option value="{{ $k }}" {{ old('condition', $inv?->condition ?? 'good') === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach(\App\Models\InventoryItem::STATUSES as $k => $label)
                    <option value="{{ $k }}" {{ old('status', $inv?->status ?? 'active') === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" maxlength="5000">{{ old('description', $inv?->description) }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" maxlength="5000">{{ old('notes', $inv?->notes) }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <hr class="my-4">
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> {{ $submit }}</button>
        <a href="{{ route('inventory-items.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
