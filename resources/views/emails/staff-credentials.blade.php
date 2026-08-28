<x-mail::message>
# {{ $isReset ? 'Password Reset' : 'Welcome to the Team' }}

Hi {{ $staffName }},

@if($isReset)
Your account password has been reset by an administrator. Use the credentials below to log in.
@else
An account has been created for you. Use the credentials below to log in.
@endif

**Email:** {{ $email }}
**Password:** {{ $password }}

<x-mail::button :url="$loginUrl">
Log In
</x-mail::button>

For security, please log in and change your password as soon as possible.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
