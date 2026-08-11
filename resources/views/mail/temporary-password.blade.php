<x-mail::message>
# {{ config('app.name') }} — Login credentials

Hello {{ $user->name }},

An administrator has issued you a temporary password for {{ config('app.name') }}, {{ config('college.institution_name', 'the college') }}'s information management system.

**Login ID:** `{{ $loginId }}`  
**Temporary password:** `{{ $temporaryPassword }}`

Sign in at: [{{ config('app.url') }}]({{ config('app.url') }})

You will be asked to **change your password** on first login.

If you did not expect this message, contact the college ICT office.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
