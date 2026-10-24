<x-mail::message>
# {{ $isReset ? 'Password Reset Notification' : 'Welcome to the Team!' }}

Dear **{{ $staffName }}**,

@if($isReset)
Your account password for the Agency Management System has been reset by an administrator. Please use the credentials below to log in.
@else
An employee workspace account has been created for you on the Agency Management System. You can access your assigned projects, tasks, attendance, and client communications here:
@endif

<x-mail::panel>
**Workspace Login:** [{{ $loginUrl }}]({{ $loginUrl }})  
**Email:** `{{ $email }}`  
**Password:** `{{ $password }}`
</x-mail::panel>

<x-mail::button :url="$loginUrl" color="primary">
Log In to Workspace
</x-mail::button>

> [!NOTE]  
> Please keep your login credentials secure. You can update your password at any time from your profile settings.

Best regards,  
**{{ config('app.name') }}**
</x-mail::message>
