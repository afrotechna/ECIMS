@php
    $canBulkDelete = ($bulkModule ?? '') !== '' && (auth()->user()?->canModule($bulkModule, 'delete') ?? false);
@endphp
@if($canBulkDelete && (int) ($bulkItemCount ?? 0) > 0)
    <th class="no-print bulk-delete-col" style="width:2.5rem">
        <input type="checkbox" class="form-check-input bulk-delete-select-all" @if(! empty($bulkTableId)) data-table="{{ $bulkTableId }}" @endif @if(! empty($bulkScopeId)) data-bulk-scope="{{ $bulkScopeId }}" @endif aria-label="Select all on this page">
    </th>
@endif
