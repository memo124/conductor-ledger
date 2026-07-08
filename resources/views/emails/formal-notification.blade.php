---
subject: {{ $notification->subject }}
---

# {{ $notification->headline }}

Estimado/a **{{ $notification->recipientName }}**,

**Fecha y hora del evento:** {{ $notification->formattedEventAt() }}

{!! nl2br(e($notification->message)) !!}

@if($notification->actionUrl)
@component('mail::button', ['url' => $notification->actionUrl])
{{ $notification->actionLabel ?? 'Ver detalle' }}
@endcomponent
@endif

@if($notification->footerNote)
@component('mail::subcopy')
{{ $notification->footerNote }}
@endcomponent
@endif

Saludos cordiales,<br>
{{ config('app.name') }}
