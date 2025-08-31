@props(['src' => null, 'story' => null, 'group' => false, 'disappearing' => false])

<div
    x-data="{ loaded: false }"

    x-init="
        if('{{ $src }}') {
            let img = new Image();
            img.src = '{{ $src }}';
            img.onload = () => loaded = true;
            img.onerror = () => loaded = false;
        }
    "
    {{ $attributes->merge([
        'class' => "shrink-0 inline-flex items-center justify-center relative transition
                    overflow-visible rounded-full border border-[var(--wc-light-secondary)] text-gray-300 bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)]
                    dark:border-[var(--wc-dark-secondary)] text-base",
    ])->class(
        $story ? 'bg-linear-to-r p-[2px] ring-2 ring-white from-purple-400 via-pink-500 to-red-500 rounded-full' : ' ',
    ) }}
>
    {{-- Image --}}
    @if ($src)
        <img
            x-cloak
            x-show="loaded"
            x-transition.opacity
            class="shrink-0 w-full h-full object-cover object-center rounded-full"
            src="{{ $src }}"
            alt="avatar"
        />
    @endif

    {{-- Fallback (initials/icon/group) --}}
    <div
        x-show="!loaded"
        class="shrink-0 w-full h-full items-center justify-center rounded-full flex"
    >
        @if($group)
            <svg class="w-full h-full text-gray-400 dark:text-gray-300 bg-gray-100 dark:bg-[var(--wc-dark-secondary)] rounded-full"
                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z"/>
            </svg>
        @else
            <svg class="w-full h-full rounded-full" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        @endif
    </div>


@if ($disappearing)
        <span dusk="disappearing_messages_icon"
            class="absolute z-50 -bottom-1 bg-white -right-2 dark:bg-gray-800 rounded-full p-px">
            <svg class="w-5 h-5" viewBox="0 0 36 36" height="36" width="36" preserveAspectRatio="xMidYMid meet"
                fill="none">
                <title>disappearing</title>
                <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"
                    d="M18 31.5C18.0909 31.5 18.1817 31.4991 18.2722 31.4973C19.1005 31.4809 19.7586 30.7961 19.7422 29.9679C19.7258 29.1396 19.041 28.4815 18.2128 28.4979C18.142 28.4993 18.0711 28.5 18 28.5V31.5ZM18 7.5C18.0711 7.5 18.142 7.5007 18.2128 7.50211C19.041 7.51853 19.7258 6.86039 19.7422 6.03213C19.7586 5.20387 19.1005 4.51912 18.2722 4.5027C18.1817 4.5009 18.0909 4.5 18 4.5V7.5ZM24.5153 6.17374C23.7901 5.77341 22.8776 6.03683 22.4772 6.76211C22.0769 7.48739 22.3403 8.39988 23.0656 8.8002C23.1891 8.86838 23.3111 8.93898 23.4316 9.01195C24.1401 9.44118 25.0625 9.21475 25.4917 8.5062C25.921 7.79765 25.6945 6.87529 24.986 6.44605C24.8311 6.35223 24.6742 6.26144 24.5153 6.17374ZM29.554 11.014C29.1247 10.3055 28.2024 10.079 27.4938 10.5083C26.7852 10.9375 26.5588 11.8599 26.9881 12.5684C27.061 12.6889 27.1316 12.8109 27.1998 12.9344C27.6001 13.6597 28.5126 13.9231 29.2379 13.5228C29.9632 13.1224 30.2266 12.2099 29.8263 11.4847C29.7386 11.3258 29.6478 11.1689 29.554 11.014ZM31.4973 17.7278C31.4809 16.8995 30.7961 16.2414 29.9679 16.2578C29.1396 16.2742 28.4815 16.959 28.4979 17.7872C28.4993 17.858 28.5 17.9289 28.5 18C28.5 18.0711 28.4993 18.142 28.4979 18.2128C28.4815 19.041 29.1396 19.7258 29.9679 19.7422C30.7961 19.7586 31.4809 19.1005 31.4973 18.2722C31.4991 18.1817 31.5 18.0909 31.5 18C31.5 17.9091 31.4991 17.8183 31.4973 17.7278ZM29.8263 24.5153C30.2266 23.7901 29.9632 22.8776 29.2379 22.4772C28.5126 22.0769 27.6001 22.3403 27.1998 23.0656C27.1316 23.1891 27.061 23.3111 26.9881 23.4316C26.5588 24.1401 26.7852 25.0625 27.4938 25.4917C28.2024 25.921 29.1247 25.6945 29.554 24.986C29.6478 24.8311 29.7386 24.6742 29.8263 24.5153ZM24.986 29.554C25.6945 29.1247 25.921 28.2024 25.4917 27.4938C25.0625 26.7852 24.1401 26.5588 23.4316 26.9881C23.3111 27.061 23.1891 27.1316 23.0656 27.1998C22.3403 27.6001 22.0769 28.5126 22.4772 29.2379C22.8776 29.9632 23.7901 30.2266 24.5153 29.8263C24.6742 29.7386 24.8311 29.6478 24.986 29.554Z">
                </path>
                <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"
                    d="M18.0001 4.5C18 4.5 17.9999 4.5 17.9998 4.5C10.5439 4.5 4.49976 10.5442 4.49976 18C4.49976 25.4558 10.5439 31.5 17.9998 31.5C17.9999 31.5 18 31.5 18.0001 31.5V28.5C18 28.5 17.9999 28.5 17.9998 28.5C12.2008 28.5 7.49976 23.799 7.49976 18C7.49976 12.201 12.2008 7.5 17.9998 7.5C17.9999 7.5 18 7.5 18.0001 7.5V4.5Z">
                </path>
                <path fill="currentColor"
                    d="M23.3247 12.0107C23.669 11.7525 24.1507 11.7867 24.455 12.091V12.091C24.7593 12.3953 24.7935 12.877 24.5353 13.2213L19.9714 19.3066C19.2589 20.2566 17.8701 20.3553 17.0304 19.5156V19.5156C16.1907 18.6759 16.2894 17.2871 17.2394 16.5746L23.3247 12.0107Z">
                </path>
            </svg>
        </span>
    @endif



</div>
