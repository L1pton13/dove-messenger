<x-mail::message>
{{-- Приветствие --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# Ошибка
@else
# 🕊️ Рады видеть вас в Dove!
@endif
@endif

{{-- Вводный текст (динамический) --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Кнопка действия --}}
@isset($actionText)
<?php
    // Здесь можно жестко задать цвет кнопок, если хочешь кастомный
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary', // По умолчанию primary (в стилях Laravel настроим его под темный Dove)
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Заключительный текст (динамический) --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Подпись --}}
@if (! empty($salutation))
{{ $salutation }}
@else
С уважением,<br>
Команда **{{ config('app.name', 'Dove Messenger') }}**
@endif

{{-- Сноска (Subcopy) на русском языке --}}
@isset($actionText)
<x-slot:subcopy>
Если кнопка «{{ $actionText }}» не открывается, скопируйте ссылку ниже и вставьте её в адресную строку вашего браузера:

<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>