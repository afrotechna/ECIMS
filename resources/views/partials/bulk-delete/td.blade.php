@php
    $canBulkDelete = ($bulkModule ?? '') !== '' && (auth()->user()?->canModule($bulkModule, 'delete') ?? false);
@endphp
@if($canBulkDelete)
    <td class="no-print bulk-delete-col">
        <input
            type="checkbox"
            class="form-check-input bulk-delete-cb"
            value="{{ $bulkRowId }}"
            aria-label="Select item"
            @if(! empty($bulkNtaLevel)) data-nta-level="{{ $bulkNtaLevel }}" @endif
            @if(! empty($bulkGroupLabel)) data-bulk-group-label="{{ $bulkGroupLabel }}" @endif
        >
    </td>
@endif
