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
        <main class="h-[calc(100vh_-_0.0rem)] w-full">
            @php
            $conversation = request()->conversation;

            @endphp


            <div class="w-full flex min-h-full h-full rounded-lg">

                <div wire:scroll
                     x-persist="chats"
                     @class([
                        'bg-inherit border-r border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] dark:bg-inherit relative w-full  grow h-full md:w-[360px] lg:w-[400px] xl:w-[500px] shrink-0 overflow-y-auto',
                        'hidden md:grid'=>$conversation,
                        'grid'=>!$conversation,
                    ])>
                      <livewire:wirechat.chats />
                </div>


                <main @class([
                        'bg-[var(--wc-light-primary)] grow w-full dark:bg-[var(--wc-dark-primary)] relative overflow-y-auto h-full min-h-full',
                        'grid'=>$conversation,
                        'hidden md:grid'=>!$conversation,
                    ])  style="contain:content">
                    @if(request()->conversation)
                        <livewire:wirechat.chat conversation="{{ request()->conversation }}" />
                    @else
                        <div class="m-auto text-center justify-center flex gap-3 flex-col items-center col-span-12">
                            <h4 class="font-medium p-2 px-3 rounded-full font-semibold bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] dark:text-white dark:font-normal">
                                @lang('wirechat::pages.chat.messages.welcome')
                            </h4>
                        </div>
                    @endif
                </main>
            </div>
        </main>


    </div>

    @livewireScripts
    @wirechatAssets
</body>

</html>
