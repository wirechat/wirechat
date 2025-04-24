<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

      <!--THEME:--ADD TO TOP OT PREVENT FLICKERING -->
      <script>

         /* Function to apply or remove the dark theme */
        function updateTheme(isDark) {
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    
        /* Check the initial theme preference */ 
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        updateTheme(darkModeMediaQuery.matches);
    
        /* listen to changed in (prefers-color-scheme: dark) */
        darkModeMediaQuery.addEventListener('change', (event) => {
            updateTheme(event.matches);
        });

        /* Add This to update theme when page is wire navigated */
        document.addEventListener('livewire:navigated', () => {
          const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
          updateTheme(darkModeMediaQuery.matches);  // Re-apply the theme based on system preference
         });
      </script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Scripts -->
   

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @wirechatStyles
</head>

<body  x-data x-cloak class="font-sans antialiased">
    <div class="min-h-screen bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]">

        <!-- Page Content -->
        <main class="h-[calc(100vh_-_0.0rem)]">
            {{ $slot }}
        </main>

    </div>

    @livewireScripts
    @wirechatAssets
</body>

</html>
