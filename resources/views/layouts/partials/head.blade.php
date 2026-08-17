<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? 'Canice Technologies Client Portal' }}</title>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon-64.png') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
