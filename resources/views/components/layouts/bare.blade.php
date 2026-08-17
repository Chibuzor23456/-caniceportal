@props(['title' => null])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('layouts.partials.head', ['title' => $title])
</head>
<body class="h-full bg-canvas font-sans text-slate-900 antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
