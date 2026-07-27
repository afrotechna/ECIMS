@php
    $currentLocale = app()->getLocale();
@endphp
<div class="lang-toggle" role="group" aria-label="{{ __('ui.language') }}">
    <a href="{{ route('locale.switch', ['locale' => 'en', 'redirect' => request()->getRequestUri()]) }}"
       class="lang-toggle__btn {{ $currentLocale === 'en' ? 'is-active' : '' }}"
       title="{{ __('ui.english') }}"
       @if($currentLocale === 'en') aria-current="true" @endif>EN</a>
    <a href="{{ route('locale.switch', ['locale' => 'sw', 'redirect' => request()->getRequestUri()]) }}"
       class="lang-toggle__btn {{ $currentLocale === 'sw' ? 'is-active' : '' }}"
       title="{{ __('ui.swahili') }}"
       @if($currentLocale === 'sw') aria-current="true" @endif>SW</a>
</div>
