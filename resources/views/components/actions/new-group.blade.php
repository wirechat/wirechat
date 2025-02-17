@props([
    'widget' => false
])


<x-wirechat::actions.open-modal
        component="wirechat.new.group"
        :widget="$widget"
        >
{{$slot}}
</x-wirechat::actions.open-modal>
