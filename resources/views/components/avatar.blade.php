@props(['src' => null, 'story' => null, 'group' => false])
<div
    {{ $attributes->merge([
            'class' => "shrink-0 inline-flex items-center justify-center 
                      overflow-hidden rounded-full border border-gray-200 text-gray-300 bg-gray-100 dark:bg-gray-600
                      dark:border-gray-500  text-base ",
        ])->class(
            $story
                ? 'bg-gradient-to-r  p-[2px] ring-2 ring-white from-purple-400 via-pink-500 to-red-500 rounded-full'
                : ' ',
        ) }}>


    @if ($src)
        <img @class([
            'shrink-0 w-full h-full object-cover object-center rounded-full',
        ]) src="{{ $src }}" />
    @endif

    @if (!$src && $group)
        {{-- <svg class="shrink-0 scale-90 w-full h-full rounded-full text-gray-300 bg-gray-100 dark:bg-gray-600" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
        </svg> --}}
     
          {{-- <svg class="shrink-0 scale-95 w-full h-full rounded-full text-gray-300 bg-gray-100 dark:bg-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path d="M8.5 4.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0ZM10.9 12.006c.11.542-.348.994-.9.994H2c-.553 0-1.01-.452-.902-.994a5.002 5.002 0 0 1 9.803 0ZM14.002 12h-1.59a2.556 2.556 0 0 0-.04-.29 6.476 6.476 0 0 0-1.167-2.603 3.002 3.002 0 0 1 3.633 1.911c.18.522-.283.982-.836.982ZM12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"></path>
          </svg> --}}

          <svg class="shrink-0  scale-90 w-full h-full rounded-full text-gray-300 bg-gray-100 dark:bg-gray-600"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z"></path>
          </svg>

  
           @elseif(!$src)
        <svg class="shrink-0 w-full h-full rounded-full" fill="currentColor" viewBox="0 0 24 24">
            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        
        {{-- <svg  class="shrink-0 w-full h-full mt-auto rounded-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
          </svg> --}}
          
    @endif


</div>
