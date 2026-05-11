@props(['icon' => null])
<button   type="button" {{ $attributes->merge(['class' => ' w-full cursor-pointer flex items-center gap-2 px-2 py-2 text-start text-sm  text-gray-700 dark:text-gray-300  bg-white dark:bg-zinc-900 hover:bg-zinc-100/70 rounded-md dark:hover:bg-zinc-800 focus:outline-hidden focus::bg-zinc-100/70 dark:focus:bg-zinc-800 transition duration-150 ease-in-out']) }}>
    @if ($icon)
        <x-wirechat::icon :icon="$icon" class="size-4 sm:size-4 " />
    @endif
    {{ $slot }}
</button>
