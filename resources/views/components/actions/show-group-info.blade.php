@props([
    'conversation' => null, //Should be conversation  ID (Int)
    'widget' => false
])


<x-wirechat::actions.open-chat-drawer 
        component="wirechat.chat.group.info"
        dusk="show_group_info"
        conversation="{{$conversation}}"
        :widget="$widget"
        >
{{$slot}}
</x-wirechat::actions.open-chat-drawer>
