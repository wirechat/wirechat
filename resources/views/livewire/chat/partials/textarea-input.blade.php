<div @class(['flex gap-2 sm:px-2 w-full'])>
    <textarea @focus-input-field.window="$el.focus()" autocomplete="off" x-model='body' x-ref="body"
              wire:loading.delay.longest.attr="disabled" wire:target="sendMessage" id="chat-input-field" autofocus
              type="text" name="message" placeholder="{{ __('wirechat::chat.inputs.message.placeholder') }}" maxlength="1700" rows="1"
              @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px';"
              @if ($this->panel()->isEnabledShiftEnter())
              @keydown.shift.enter.prevent="insertNewLine($el)" {{-- @keydown.enter.prevent prevents the
           default behavior of Enter key press only if Shift is not held down. --}} @keydown.enter.prevent=""
              @keyup.enter.prevent="$event.shiftKey ? null : (((body && body?.trim().length > 0) || ($wire.media && $wire.media.length > 0)) ? $wire.sendMessage() : null)"
              @endif
              class="wc-textarea bg-inherit dark:bg-inherit w-full disabled:cursor-progress resize-none h-auto max-h-20  sm:max-h-72 flex grow border-0 outline-0 focus:border-0 focus:ring-0  hover:ring-0 rounded-lg   dark:text-white bg-none dark:bg-inherit  focus:outline-hidden   "
              x-init="
          @if($hasEmojiPicker)
        document.querySelector('emoji-picker')
            .addEventListener('emoji-click', event => {
                const emoji = event.detail['unicode'];
                const inputField = $refs.body;

                // Get the current cursor position (start and end)
                const startPos = inputField.selectionStart;
                const endPos = inputField.selectionEnd;

                // Get current value of the input field
                const currentValue = inputField.value;

                // Insert the emoji at the cursor position, preserving line breaks and spaces
                const newValue = currentValue.substring(0, startPos) + emoji + currentValue.substring(endPos);

                // Update Alpine.js model (x-model='body') with the new value
                inputField._x_model.set(newValue);

                // Set the cursor position after the inserted emoji
                inputField.setSelectionRange(startPos + emoji.length, startPos + emoji.length);

                // Ensure the textarea resizes correctly after adding the emoji
                inputField.style.height = 'auto';
                inputField.style.height = inputField.scrollHeight + 'px';
            });
        @endif
            "

    ></textarea>
</div>
