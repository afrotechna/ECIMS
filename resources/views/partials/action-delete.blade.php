@props([
    'label' => null,
    'title' => 'Delete',
    'iconOnly' => true,
    'submit' => false,
    'swalTitle' => null,
    'swalText' => null,
    'class' => '',
])

<button type="{{ $submit ? 'submit' : 'button' }}"
        class="btn btn-sm btn-cohas-delete {{ $class }}"
        title="{{ $title }}"
        aria-label="{{ $title }}"
        @if($swalTitle) data-swal-confirm data-swal-title="{{ $swalTitle }}" data-swal-icon="warning" @endif
        @if($swalText) data-swal-text="{{ $swalText }}" @endif
        {{ $attributes }}>
    <i class="bi bi-trash-fill" aria-hidden="true"></i>
    @if($label)
        <span class="ms-1">{{ $label }}</span>
    @elseif(! $iconOnly)
        <span class="ms-1">{{ $title }}</span>
    @endif
</button>
