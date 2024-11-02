<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

      <!-- JavaScript to prevent flickering -->
      <script>
        // Function to apply or remove the dark theme
        function updateTheme(isDark) {
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    
        // Check the initial theme preference
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        updateTheme(darkModeMediaQuery.matches);
    
        // Listen for changes in the theme preference
        darkModeMediaQuery.addEventListener('change', (event) => {
            updateTheme(event.matches);
        });
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

        emoji-picker #search {
            background: red;
        }
  
    

        /* Emoji picker configuration */
        emoji-picker {
            --background: #f9fafb;
            --border-radius: 12px;
            --input-border-color:rgb(229 229 229);
            --input-padding:0.45rem;
            --outline-color: none;
            --outline-size: 1px;
            --num-columns: 8; /* Mobile-first default */
            --emoji-padding: 0.7rem;
            --emoji-size: 1.5rem; /* Smaller size for mobile */
            --border-color: none;
            --indicator-color:#9ca3af;
        }
  

        @media screen and (min-width: 600px) {
            emoji-picker {
                --num-columns: 10; /* Increase columns for larger screens */
                --emoji-size: 1.9rem; /* Larger size for desktop */
            }
        }

        @media screen and (min-width: 900px) {
            emoji-picker {
                --num-columns: 16; /* Increase columns for larger screens */
                --emoji-size: 1.9rem; /* Larger size for desktop */
            }
        }

        /* Emoji picker Dark mode configuration */
        @media (prefers-color-scheme: dark) {
            emoji-picker {
                --background: #1f2937;
                --input-border-color: #374151;
                --outline-color:none;
                --outline-size:1px;
                --border-color: none;
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
    @livewireScripts
    @once
    @livewire('wirechat-modal')
   @endonce
</body>

</html>
