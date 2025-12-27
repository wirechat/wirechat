@props([
    'conversation' => null, //Should be conversation to more flexible when overriding
    'widget' => false,
    'isDropdown' => false,
])


<x-wirechat::actions.open-chat-drawer 
        component="wirechat.chat.group.info"
        dusk="show_group_info"
        conversation="{{$conversation->id}}"
        :widget="$widget"
        >
{{$slot}}
</x-wirechat::actions.open-chat-drawer>
