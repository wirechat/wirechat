
@props([
    'position'=>'bottom',
    'popoverOffset'=>'20'

    ]
)
<div x-data="{
    popoverOpen: false,
    popoverArrow: false,
    popoverPosition: 'top',
    popoverHeight: 0,
    popoverOffset: 40,
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
        if(value){
            popoverPositionCalculate();
            let el = document.getElementById('width');
            if(el){
                el.focus();
            }
        }
    });
"
class="relative overflow-visible">

<button {{$trigger->attributes->class(["flex items-center cursor-pointer hover:scale-105 transition-transform justify-center disabled:cursor-progress"] )}} type="button" x-ref="popoverButton" @click="popoverOpen=!popoverOpen">
     {{$trigger}}
</button>

<div x-ref="popover"
    x-anchor.offset.17="$refs.popoverButton"  
    x-show="popoverOpen"

    x-init="setTimeout(function(){ popoverHeightCalculate(); }, 100);"
    @click.away="popoverOpen=false;"
    @keydown.escape.window="popoverOpen=false"
    class=" min-w-[13rem]  max-w-fit " 
    x-cloak
    @click="popoverOpen=false" >
    <div 
    
    
    x-ref="popoverInner" x-show="popoverOpen" class="w-full p-2 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-secondary)]  border border-[var(--wc-light-secondary)]  dark:border-[var(--wc-dark-primary)] rounded-lg shadow-sm ">
        <div x-show="popoverArrow && popoverPosition == 'bottom'" class="absolute top-0 inline-block w-5 mt-px overflow-hidden -translate-x-2 -translate-y-2.5 left-1/2"><div class="w-2.5 h-2.5 origin-bottom-left transform rotate-45 bg-white border-t border-l rounded-xs"></div></div>
        <div x-show="popoverArrow  && popoverPosition == 'top'" class="absolute bottom-0 inline-block w-5 mb-px overflow-hidden -translate-x-2 translate-y-2.5 left-1/2"><div class="w-2.5 h-2.5 origin-top-left transform -rotate-45 bg-white border-b border-l rounded-xs"></div></div>
        <div class="grid gap-4">
            {{$slot}}
        </div>
    </div>
</div>
</div>