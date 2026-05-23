<x-mail::message>
# Your Login Verification Code

Please use the following 6-digit code to securely log in to your account.

<div style="background-color: #f3f4f6; padding: 16px; border-radius: 8px; text-align: center; margin-bottom: 24px;">
<h1 style="letter-spacing: 4px; color: #2563eb; margin: 0; font-size: 32px;">{{ $otpCode }}</h1>
</div>

This code will expire in 5 minutes. If you did not request this code, please ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
