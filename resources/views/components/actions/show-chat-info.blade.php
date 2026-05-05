@props([
    'conversation' => null, //Should be conversation  ID (Int)
    'widget' => false,
    'panel' => null,
])


<x-wirechat::actions.open-chat-drawer 
        component="wirechat.chat.info"
        dusk="show_chat_info"
        conversation="{{$conversation}}"
        :panel="$panel"
        :widget="$widget"
        >
{{$slot}}
</x-wirechat::actions.open-chat-drawer>
