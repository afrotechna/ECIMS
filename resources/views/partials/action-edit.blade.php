@props(['href', 'label' => null, 'title' => 'Edit', 'iconOnly' => false, 'class' => ''])

<a href="{{ $href }}"
   class="btn btn-sm btn-cohas-edit {{ $class }}"
   title="{{ $title }}"
   aria-label="{{ $title }}">
    <i class="bi bi-pencil-square" aria-hidden="true"></i>
    @unless($iconOnly)
        <span class="ms-1">{{ $label ?? $title }}</span>
    @endunless
</a>
