@component('mail::message')
# {{ config('app.name') }}

{!! nl2br(e($mailBody)) !!}

Thanks,<br>
{{ config('college.institution_name', config('app.name')) }}
@endcomponent
