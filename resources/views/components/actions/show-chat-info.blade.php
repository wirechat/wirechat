@props([
    'conversation' => null, //Should be conversation to more flexible when overriding
    'widget' => false
])


<x-wirechat::actions.open-chat-drawer 
        component="wirechat.chat.info"
        dusk="show_chat_info"
        conversation="{{$conversation->id}}"
        :widget="$widget"
        >
{{$slot}}
</x-wirechat::actions.open-chat-drawer>
