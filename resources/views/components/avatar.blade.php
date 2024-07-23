@props(['src'=>null,'story'=>null])
<div 
    {{$attributes->merge([
        'class'=>"shrink-0 inline-flex items-center justify-center 
                  overflow-hidden rounded-full border border-gray-200
                  dark:border-gray-500  text-base "])
                  ->class($story?'bg-gradient-to-r  p-[2px] ring-2 ring-white from-purple-400 via-pink-500 to-red-500 rounded-full':' ')
                  }}>
 
 
    @if ($src)
        <img @class([ 'shrink-0 w-full h-full object-cover object-center rounded-full', ])
            src="{{ $src }}"
        />
    @endif

    @if (!$src)
        <svg
            class="shrink-0 w-full h-full rounded-full text-gray-300 bg-gray-100 dark:bg-gray-600"
            fill="currentColor"
            viewBox="0 0 24 24">
            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
    @endif
</div>

