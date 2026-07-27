@php
    $canBulkDelete = ($bulkModule ?? '') !== '' && (auth()->user()?->canModule($bulkModule, 'delete') ?? false);
@endphp
@if($canBulkDelete)
    <input type="checkbox" class="form-check-input bulk-delete-cb me-2 align-middle" value="{{ $bulkRowId }}" aria-label="Select item">
@endif
