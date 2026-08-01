@php($maintenanceSetting = \App\Models\MaintenanceSetting::current())
@if($maintenanceSetting->is_active)
<div class="maintenance-banner" role="status">
    <i class="bi bi-cone-striped" aria-hidden="true"></i>
    <div class="maintenance-banner-copy">
        <strong>{{ $maintenanceSetting->title ?: 'Scheduled maintenance' }}</strong>
        <span>{{ $maintenanceSetting->message ?: 'The system will be temporarily unavailable for maintenance.' }}</span>
        @if($maintenanceSetting->starts_at || $maintenanceSetting->ends_at)
        <span class="maintenance-banner-schedule">
            @if($maintenanceSetting->starts_at){{ $maintenanceSetting->isUpcoming() ? 'Starts' : 'Started' }} {{ $maintenanceSetting->starts_at->format('d M Y, H:i') }}@endif
            @if($maintenanceSetting->ends_at) &middot; back by {{ $maintenanceSetting->ends_at->format('d M Y, H:i') }}@endif
        </span>
        @endif
    </div>
</div>
@endif
