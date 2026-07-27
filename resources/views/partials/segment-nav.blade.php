@php
    $navItems = $items ?? [];
    $colCount = max(1, min(6, (int) ($columns ?? count($navItems))));
@endphp
<nav class="cohas-segment-nav mb-3" aria-label="{{ $ariaLabel ?? $eyebrow ?? 'Navigation' }}">
    <div class="cohas-segment-nav__header">
        <span class="cohas-segment-nav__eyebrow">{{ $eyebrow ?? 'Browse' }}</span>
        @if(! empty($clearUrl))
            <a href="{{ $clearUrl }}" class="cohas-segment-nav__clear">
                @if(! empty($clearIcon))<i class="bi {{ $clearIcon }} me-1" aria-hidden="true"></i>@endif
                {{ $clearLabel ?? 'View all' }}
            </a>
        @elseif(! empty($hint))
            <span class="cohas-segment-nav__hint text-muted">{{ $hint }}</span>
        @endif
    </div>
    <div class="cohas-segment-nav__grid cohas-segment-nav__grid--cols-{{ $colCount }}">
        @foreach($navItems as $item)
            <a
                href="{{ $item['href'] }}"
                class="cohas-segment-nav__item {{ $item['accent'] ?? '' }} {{ ! empty($item['active']) ? 'is-active' : '' }}"
                @if(! empty($item['active'])) aria-current="page" @endif
            >
                <span class="cohas-segment-nav__icon" aria-hidden="true"><i class="bi {{ $item['icon'] ?? 'bi-circle' }}"></i></span>
                <span class="cohas-segment-nav__body">
                    <span class="cohas-segment-nav__title">{{ $item['title'] }}</span>
                    @if(! empty($item['subtitle']))
                        <span class="cohas-segment-nav__count">{{ $item['subtitle'] }}</span>
                    @endif
                </span>
                <span class="cohas-segment-nav__chevron" aria-hidden="true"><i class="bi bi-arrow-right-short"></i></span>
            </a>
        @endforeach
    </div>
</nav>
