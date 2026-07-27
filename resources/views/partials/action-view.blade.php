@props(['href', 'label' => null, 'title' => 'View', 'iconOnly' => true, 'class' => ''])

<a href="{{ $href }}"
   class="btn btn-sm btn-outline-primary {{ $class }}"
   title="{{ $title }}"
   aria-label="{{ $title }}">
    <i class="bi bi-eye" aria-hidden="true"></i>
    @unless($iconOnly)
        <span class="ms-1">{{ $label ?? $title }}</span>
    @endunless
</a>
