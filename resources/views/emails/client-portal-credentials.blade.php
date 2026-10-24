<x-mail::message>
# Welcome to the Client Portal

Dear **{{ $clientName }}**,

Your client portal account has been prepared and activated. You can use your secure portal to review your company's projects, estimates, quotations, shared files, and communicate directly with your project team.

<x-mail::panel>
**Portal URL:** [{{ $loginUrl }}]({{ $loginUrl }})  
**Username / Email:** `{{ $email }}`  
**Temporary Password:** `{{ $password }}`
</x-mail::panel>

<x-mail::button :url="$loginUrl" color="primary">
Access Client Portal
</x-mail::button>

> [!NOTE]  
> For your account security, we recommend changing your password right after your initial login.

If you have any questions or require assistance, simply reply to this email or reach out to your assigned account manager.

Best regards,  
**{{ config('app.name') }} Team**
</x-mail::message>
