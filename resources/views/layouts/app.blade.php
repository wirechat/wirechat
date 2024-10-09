<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

      <!-- JavaScript to prevent flickering -->
      <script>
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Scripts -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js" defer></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Define root properties */

     

        /* Emoji picker configuration */
        emoji-picker {
            --background: white;
            --border-radius: 12px;
            --input-border-color: #e5e7eb;
            --outline-color:#e5e7eb;
            --outline-size:1px;
            --emoji-padding: 0.6rem;
        }

        /* Emoji picker Dark mode configuration */
        @media (prefers-color-scheme: dark) {
            emoji-picker {
                --background: #1f2937;
                --input-border-color: #374151;
                --outline-color:#9ca3af;
                --outline-size:1px;
                --border-color: #374151;
                --input-font-color: white;
                --indicator-color:#9ca3af;
                --button-hover-background:#9ca3af
            }
        }
    </style>

    @livewireStyles
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-white dark:bg-gray-900">

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>

    </div>
    <x-wirechat::toast />
    @livewireScripts
    @once
    @livewire('wirechat-modal')
   @endonce
</body>

</html>
