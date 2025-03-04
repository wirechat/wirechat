<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <livewire:styles />
    @wirechatStyles
</head>
<body>
    {{ $slot }}

    <livewire:scripts />
    @wirechatAssets

</body>
</html>