@component('mail::message')
# OTP VERIFICATION
    {{$data}}
The body of your message.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
