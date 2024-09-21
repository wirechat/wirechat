<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <livewire:styles />
</head>
<body>
    {{ $slot }}

    <livewire:scripts />
</body>
</html>