<x-mail::message>
# Welcome to the Client Portal

Hi {{ $clientName }},

An account has been created for you on the client portal. You can use it to view your
company's projects, estimates, quotations, and payments.

Use the credentials below to log in.

**Email:** {{ $email }}
**Temporary Password:** {{ $password }}

<x-mail::button :url="$loginUrl">
Log In
</x-mail::button>

For security, please log in and change your password as soon as possible.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
