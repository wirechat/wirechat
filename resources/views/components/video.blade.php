@props([
    'source'=>null,
    'controls'=>true,
    'cover'=>true,
    'height'=>"auto",
    'showToggleSound'=>true,



])


  <div x-data="{playing:false,muted:false}"
      class="relative "
      @click.outside="$refs.player.pause()"
      x-intersect:leave="$refs.player.pause()">


        <video x-ref="player" src="{{$source}}" @play="playing=true" @pause="playing=false"
              class="  w-auto dark:bg-gray-600    border rounded-xl  border-gray-50 dark:border-gray-700 rounded-xl {{$cover==true?'object-cover':''}} {{$height}}">
            your browser does not support html5 video 
        </video>

        @if ($controls==true)
            

        {{-- play --}}

        <div x-cloak x-show="!playing" @click="$refs.player.play()" class="absolute z-10 inset-0 flex items-center justify-center w-full h-full cursor-pointer">
            <svg  xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-play-fill w-16 h-16 text-white" viewBox="0 0 16 16">
                <path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
              </svg>
        </div>

        {{-- pause button --}}
        <div x-show="playing" @click="$refs.player.pause()" class="absolute z-10 inset-0 flex items-center justify-center w-full h-full cursor-pointer">
            
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pause-fill  w-16 h-16 text-white invisible" viewBox="0 0 16 16">
                <path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/>
              </svg>
        </div>



        {{-- mute  --}}

        @if ($showToggleSound==true)
        <div class="absolute z-100 bottom-2 right-2 m-4 bg-gray-900 text-white rounded-lg p-1 cursor-pointer">

          {{-- mute --}}

          <svg x-cloak x-show="!muted" @click="$refs.player.muted=true;muted=true"  xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-volume-mute-fill w-h w-4" viewBox="0 0 16 16">
              <path d="M6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06zm7.137 2.096a.5.5 0 0 1 0 .708L12.207 8l1.647 1.646a.5.5 0 0 1-.708.708L11.5 8.707l-1.646 1.647a.5.5 0 0 1-.708-.708L10.793 8 9.146 6.354a.5.5 0 1 1 .708-.708L11.5 7.293l1.646-1.647a.5.5 0 0 1 .708 0z"/>
            </svg>


            <svg  x-show="muted" @click="$refs.player.muted=false;muted=false" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-volume-off-fill w-4 h-4" viewBox="0 0 16 16">
              <path d="M10.717 3.55A.5.5 0 0 1 11 4v8a.5.5 0 0 1-.812.39L7.825 10.5H5.5A.5.5 0 0 1 5 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06z"/>
            </svg>



          {{-- unmute  --}}


      </div>
        @endif

      
        @endif


</div>
