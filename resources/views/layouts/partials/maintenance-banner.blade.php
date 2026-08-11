@php($maintenanceSetting = \App\Models\MaintenanceSetting::current())
@if($maintenanceSetting->isUpcoming())
<div class="maintenance-banner" role="status">
    <i class="bi bi-cone-striped" aria-hidden="true"></i>
    <div class="maintenance-banner-copy">
        <strong>{{ $maintenanceSetting->title ?: 'Scheduled maintenance' }}</strong>
        <span>{{ $maintenanceSetting->message ?: 'The system will be temporarily unavailable for maintenance.' }}</span>
        <span class="maintenance-banner-schedule">
            Starts {{ $maintenanceSetting->starts_at->format('d M Y, H:i') }}
            @if($maintenanceSetting->ends_at) &middot; back by {{ $maintenanceSetting->ends_at->format('d M Y, H:i') }}@endif
        </span>
    </div>
</div>
@endif
