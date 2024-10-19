<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    <livewire:styles />
</head>
<body>
    {{ $slot }}

    <livewire:scripts />
    @livewire('wirechat-modal')
</body>
</html>