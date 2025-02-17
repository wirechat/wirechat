@props([
    'widget' => false
])


<x-wirechat::actions.open-modal
        component="wirechat.new.chat"
        :widget="$widget"
        >
{{$slot}}
</x-wirechat::actions.open-modal>
