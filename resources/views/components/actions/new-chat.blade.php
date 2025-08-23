@props([
    'widget' => false,
    'panel'=>null
])


<x-wirechat::actions.open-modal
        component="wirechat.new.chat"
        :widget="$widget"
        :panel="$panel"
        >
{{$slot}}
</x-wirechat::actions.open-modal>
