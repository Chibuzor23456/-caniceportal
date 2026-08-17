@component('mail::message')
# Welcome to Canice Technologies

Hi {{ $client->contact_person }},

An account has been created for **{{ $client->company_name }}** on the Canice Technologies client portal. Everything about your project (quotations, files, invoices, and updates) will live here going forward.

**Your login details:**

Email: {{ $client->email }}
Temporary password: {{ $temporaryPassword }}

You'll be asked to set a new password the first time you sign in.

@component('mail::button', ['url' => $loginUrl])
Log In
@endcomponent

If you weren't expecting this, please contact us and let us know.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
