@props([
    'position' => 'top',
    'spacing' => 0.6,
    'content'=>null,
    'tooltipVisible' => false,
    'text' => 'Tooltip text',
    'arrow' => true,
])

<div
    x-data="{
        tooltipPosition: @js($position),
        tooltipVisible: @js($tooltipVisible),
        tooltipText: @js($text),
        tooltipArrow: @js($arrow),
        tooltipId: null,
    }"
    x-init="
        tooltipId = $id('tooltip');
        const clearAriaOnActive = () => {
            if ($refs.content.contains(document.activeElement)) {
                document.activeElement.removeAttribute('aria-describedby');
            }
        };
        $refs.content.addEventListener('mouseenter', () => { tooltipVisible = true; });
        $refs.content.addEventListener('mouseleave', () => {
            if ($refs.content.contains(document.activeElement)) return;
            tooltipVisible = false;
        });
        $refs.content.addEventListener('focusin', (event) => {
            tooltipVisible = true;
            event.target.setAttribute('aria-describedby', tooltipId);
        });
        $refs.content.addEventListener('focusout', (event) => {
            event.target.removeAttribute('aria-describedby');
            const next = event.relatedTarget;
            if (next && $refs.content.contains(next)) return;
            tooltipVisible = false;
        });
        $refs.content.addEventListener('touchstart', (event) => {
            event.stopPropagation();
            tooltipVisible = true;
        }, { passive: true });
        const hideOnOutsideTouch = (event) => {
            if (!$el.contains(event.target)) {
                clearAriaOnActive();
                tooltipVisible = false;
            }
        };
        document.addEventListener('touchstart', hideOnOutsideTouch, { passive: true });
        $cleanup(() => document.removeEventListener('touchstart', hideOnOutsideTouch));
    "
    :style="`--tooltip-space: ${@js($spacing)}rem`"
    class="relative "
>
    <div
        x-bind:x-anchor="`${tooltipPosition} $refs.content`"
        x-ref="tooltip"
        x-show="tooltipVisible"
        x-cloak
        role="tooltip"
        :id="tooltipId"
        class="absolute w-auto text-sm"

        :style="{
            marginTop: tooltipPosition === 'top' ? `calc(var(--tooltip-space) * -1)` : null,
            marginBottom: tooltipPosition === 'bottom' ? `calc(var(--tooltip-space) * -1)` : null,
            marginLeft: tooltipPosition === 'left' ? `calc(var(--tooltip-space) * -1)` : null,
            marginRight: tooltipPosition === 'right' ? `calc(var(--tooltip-space) * -1)` : null,
        }"
    >
        <div
            x-transition
            class="relative px-2 py-1.5 text-black dark:text-white rounded bg-white  dark:bg-zinc-800"
        >
            @if($content)
                {{$content}}
            @else
                <p x-text="tooltipText" class="text-xs whitespace-nowrap"></p>

            @endif

            <div
                x-show="tooltipArrow"
                class="absolute inline-flex items-center justify-center overflow-hidden"
                :class="{
                    'bottom-0 left-1/2 -translate-x-1/2 translate-y-full w-2.5': tooltipPosition === 'top',
                    'right-0 top-1/2 -translate-y-1/2 translate-x-full h-2.5': tooltipPosition === 'left',
                    'top-0 left-1/2 -translate-x-1/2 -translate-y-full w-2.5': tooltipPosition === 'bottom',
                    'left-0 top-1/2 -translate-y-1/2 -translate-x-full h-2.5': tooltipPosition === 'right',
                }"
            >
                <div
                    class="w-1.5 h-1.5 bg-black/90 transform"
                    :class="{
                        '-rotate-45 origin-top-left': tooltipPosition === 'top',
                        'rotate-45 origin-top-left': tooltipPosition === 'left',
                        'rotate-45 origin-bottom-left': tooltipPosition === 'bottom',
                        '-rotate-45 origin-top-right': tooltipPosition === 'right',
                    }"
                ></div>
            </div>
        </div>
    </div>

    <div x-ref="content">
        {{ $slot }}
    </div>
</div>
