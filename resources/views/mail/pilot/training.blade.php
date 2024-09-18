@component('mail::message')

{{-- Greeting --}}
# Hello {{ $firstName }},

{{-- Intro Lines --}}
@foreach ($textLines as $line)
{{ $line }}
@endforeach

@if ($url1)
Wiki - {{ $url1 }}
@endif
@if ($url2)
Moodle - {{ $url2 }}
@endif

{{-- Action Button --}}
@isset($actionUrl)
@component('mail::button', ['url' => $actionUrl, 'color' => $actionColor])
{{ $actionText }}
@endcomponent
@endisset

{{-- Subcopy --}}
@isset($contactMail)
@slot('subcopy')
For questions regarding your training, contact [jere.heiskanen@vatsim-scandinavia.org](mailto:jere.heiskanen@vatsim-scandinavia.org)
@endslot
@endisset

@endcomponent