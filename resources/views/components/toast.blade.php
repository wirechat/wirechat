

<div x-data="{
    bannerVisible: false,
    type:'default',
    message:' ',
    bannerVisibleAfter: 300,
    counter: 3000, 
    timer: null,
    closeToast:function(){

        this.bannerVisible = false;
        clearInterval(this.timer);

    }
    }"

    @wirechat-toast.window="
        message = $event.detail.message;
        type = $event.detail.type || 'default';
        bannerVisible = true;
        counter = 3000;
        if (timer) clearInterval(timer);
        timer = setInterval(() => {
            if (counter <= 0) {
                bannerVisible = false;
                clearInterval(timer);
            } else {
                counter -= 100;
            }
        }, 100);
    "
    @mouseenter="clearInterval(timer)"
    @mouseleave="
        timer = setInterval(() => {
            if (counter <= 0) {
                bannerVisible = false;
                clearInterval(timer);
            } else {
                counter -= 100;
            }
        }, 100);
    "
    x-show="bannerVisible"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="-translate-y-10"
    x-transition:enter-end="translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="translate-y-0"
    x-transition:leave-end="-translate-y-10"
   
    
    class="fixed sm:top-2 top-0 z-[100] inset-x-0 sm:max-w-md mx-auto sm:ml-auto w-full h-auto py-2.5 mr-2 duration-300 ease-out bg-white dark:bg-zinc-900 shadow-md sm:border border-zinc-200 dark:border-zinc-700 rounded-md text-zinc-900 dark:text-zinc-100" x-cloak>
    <div class="flex items-center justify-between w-full h-full  px-3 mx-auto max-w-7xl ">
        <div class="flex items-center gap-3 w-full h-full ">

            <span x-show="type=='warning'" x-cloak>
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" >
                    <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                  </svg>
            </span>

            <span x-show="type=='info'" x-cloak>
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75 4.365 9.75 9.75 9.75S2.25 17.385 2.25 12Zm8.25-1.5a.75.75 0 0 1 1.5 0v6a.75.75 0 0 1-1.5 0v-6ZM12 7.5a.75.75 0 1 0-1.5 0 .75.75 0 0 0 1.5 0Z" clip-rule="evenodd" />
                  </svg>
            </span>

            <span x-show="type=='error'" x-cloak>
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Z" clip-rule="evenodd" />
                  </svg>
            </span>

            <span x-show="type=='danger'" x-cloak>
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 0 1 .67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 1 1-.671-1.34l.041-.022ZM12 9a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                  </svg>

            </span>

            <span x-show="type=='success'" x-cloak>
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"  >
                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                  </svg>
            </span>

            <p
                class="text-xs"
                :class="{
                    'text-yellow-700 dark:text-yellow-300': type === 'warning',
                    'text-blue-700 dark:text-blue-300': type === 'info',
                    'text-red-700 dark:text-red-300': type === 'error',
                    'text-red-700 dark:text-red-300': type === 'danger',
                    'text-green-700 dark:text-green-300': type === 'success',
                    'text-zinc-900 dark:text-zinc-100': !['warning', 'info', 'error', 'danger', 'success'].includes(type),
                }"
                x-text="message"></p>
        </div>

        <button @click="closeToast()" class="flex items-center shrink-0 translate-x-1 ease-out duration-150 justify-center w-6 h-6 p-1.5 text-zinc-700 dark:text-zinc-200 rounded-full hover:bg-neutral-100 dark:hover:bg-zinc-800">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-full h-full"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>
</div>
