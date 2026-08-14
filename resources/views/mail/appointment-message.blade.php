<x-mail::message>
# {{ $greeting }}

@foreach ($bodyLines as $line)
{{ $line }}

@endforeach

@if ($actionUrl !== null && $actionLabel !== null)
<x-mail::button :url="$actionUrl" color="primary">
{{ $actionLabel }}
</x-mail::button>
@endif

@if ($secondaryActionUrl !== null && $secondaryActionLabel !== null)
<x-mail::button :url="$secondaryActionUrl" color="error">
{{ $secondaryActionLabel }}
</x-mail::button>
@endif

Se os botões não abrirem, use os links abaixo:

@if ($actionUrl !== null && $actionLabel !== null)
[{{ $actionLabel }}]({{ $actionUrl }})
@endif

@if ($secondaryActionUrl !== null && $secondaryActionLabel !== null)
[{{ $secondaryActionLabel }}]({{ $secondaryActionUrl }})
@endif

Mensagem enviada pelo AgendaFlow.
</x-mail::message>
