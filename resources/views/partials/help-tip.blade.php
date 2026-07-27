@props(['text', 'placement' => 'top', 'icon' => 'bi-lightbulb-fill', 'label' => 'Help'])

<button type="button"
        class="help-tip"
        data-bs-toggle="popover"
        data-bs-trigger="click"
        data-bs-placement="{{ $placement }}"
        data-bs-html="false"
        data-bs-content="{{ $text }}"
        aria-label="{{ $label }}">
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
</button>
