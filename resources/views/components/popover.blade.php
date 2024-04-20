
@props([
    'position'=>'bottom']
)
<div x-data="{
    popoverOpen: false,
    popoverArrow: false,
    popoverPosition: 'top',
    popoverHeight: 0,
    popoverOffset: 20,
    popoverHeightCalculate() {
        this.$refs.popover.classList.add('invisible'); 
        this.popoverOpen=true; 
        let that=this;
        $nextTick(function(){ 
            that.popoverHeight = that.$refs.popover.offsetHeight;
            that.popoverOpen=false; 
            that.$refs.popover.classList.remove('invisible');
            that.$refs.popoverInner.setAttribute('x-transition', '');
            that.popoverPositionCalculate();
        });
    },
    popoverPositionCalculate(){
        if(window.innerHeight < (this.$refs.popoverButton.getBoundingClientRect().top + this.$refs.popoverButton.offsetHeight + this.popoverOffset + this.popoverHeight)){
            this.popoverPosition = 'top';
        } else {
            this.popoverPosition = 'bottom';
        }
    }
}"
x-init="
    that = this;
    window.addEventListener('resize', function(){
        popoverPositionCalculate();
    });
    $watch('popoverOpen', function(value){
        if(value){ popoverPositionCalculate(); document.getElementById('width').focus();  }
    });
"
class="relative overflow-visible">

<button x-ref="popoverButton" @click="popoverOpen=!popoverOpen" class="flex items-center justify-center w-10 h-10 bg-white border rounded-full shadow-sm cursor-pointer hover:bg-neutral-100 focus-visible:ring-gray-400 focus-visible:ring-2 focus-visible:outline-none active:bg-white border-neutral-200/70">
     {{$trigger}}
</button>

<div x-ref="popover"
    x-show="popoverOpen"
    x-init="setTimeout(function(){ popoverHeightCalculate(); }, 100);"
    x-trap.inert="popoverOpen"
    @click.away="popoverOpen=false;"
    @keydown.escape.window="popoverOpen=false"
    :class="{ 'top-0 mt-12' : popoverPosition == 'bottom', 'bottom-0 mb-12' : popoverPosition == 'top' }"
    class="absolute min-w-[13rem]  max-w-fit  z-50 -left-3" x-cloak
    @click="popoverOpen=false"
    >
    <div x-ref="popoverInner" x-show="popoverOpen" class="w-full p-2 bg-white border rounded-lg shadow-sm border-neutral-200/70">
        <div x-show="popoverArrow && popoverPosition == 'bottom'" class="absolute top-0 inline-block w-5 mt-px overflow-hidden -translate-x-2 -translate-y-2.5 left-1/2"><div class="w-2.5 h-2.5 origin-bottom-left transform rotate-45 bg-white border-t border-l rounded-sm"></div></div>
        <div x-show="popoverArrow  && popoverPosition == 'top'" class="absolute bottom-0 inline-block w-5 mb-px overflow-hidden -translate-x-2 translate-y-2.5 left-1/2"><div class="w-2.5 h-2.5 origin-top-left transform -rotate-45 bg-white border-b border-l rounded-sm"></div></div>
        <div class="grid gap-4">
            {{$slot}}
        </div>
    </div>
</div>
</div>