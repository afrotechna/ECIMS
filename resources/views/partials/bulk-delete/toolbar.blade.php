@php
    $canBulkDelete = ($bulkModule ?? '') !== '' && (auth()->user()?->canModule($bulkModule, 'delete') ?? false);
    $bulkItemCount = (int) ($bulkItemCount ?? 0);
@endphp
@if($canBulkDelete && $bulkItemCount > 0 && ! empty($bulkAction))
    <form
        id="{{ $bulkFormId ?? 'bulkDeleteForm' }}"
        method="POST"
        action="{{ $bulkAction }}"
        class="d-inline m-0 bulk-delete-form no-print"
        @if(! empty($bulkTableId)) data-bulk-table="{{ $bulkTableId }}" @endif
        @if(! empty($bulkScopeId)) data-bulk-scope="{{ $bulkScopeId }}" @endif
        data-bulk-confirm="{{ $bulkConfirm ?? 'Delete :count selected item(s)? This cannot be undone.' }}"
    >
        @csrf
        <div class="bulk-delete-ids"></div>
        @foreach($bulkHidden ?? [] as $name => $value)
            @if($value !== null && $value !== '')
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach
        <button type="submit" class="btn btn-sm btn-cohas-delete bulk-delete-submit d-none" title="{{ $bulkButtonLabel ?? 'Delete selected' }}" aria-label="{{ $bulkButtonLabel ?? 'Delete selected' }}" data-label-base="{{ $bulkButtonLabel ?? 'Delete selected' }}">
            <i class="bi bi-trash-fill" aria-hidden="true"></i>
            <span class="badge bg-light text-danger ms-1 bulk-delete-count">0</span>
        </button>
    </form>
@endif
