<?php

namespace Namu\WireChat\Livewire\Chat;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Index extends Component
{
    #[Layout('wirechat::layouts.app')]
    #[Title('Chats')]
    public function render()
    {
        return <<<'BLADE'
              <div class="w-full h-[calc(100vh_-_0.0rem)] flex  rounded-lg" >
                  <div class="relative  w-full h-full   md:w-[360px] lg:w-[400px] xl:w-[500px] shrink-0 overflow-y-auto  ">
                    <livewire:chats/> 
                  </div>
                  <main class=" hidden md:grid   w-full  dark:border-gray-700 h-full relative overflow-y-auto"  style="contain:content">

                  <div class="m-auto text-center justify-center flex gap-3 flex-col  items-center  col-span-12">

                           <h4 class="font-medium p-2 px-3 rounded-full font-semibold bg-gray-50 dark:bg-gray-800 dark:text-white dark:font-normal">@lang('Select a conversation to start messaging')</h4>
                        
                      </div>
                  </main>
              </div>
      BLADE;
    }
}
