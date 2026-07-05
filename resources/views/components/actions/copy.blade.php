@props([
    'value',
    'successMessage',
    'promptMessage',
])

<div
    {{ $attributes }}
    onclick="
        event.preventDefault();
        event.stopPropagation();

        const value = @js($value ?? '');
        const successMessage = @js($successMessage);
        const promptMessage = @js($promptMessage);
        const notifyCopied = () => window.dispatchEvent(new CustomEvent('wirechat-toast', {
            detail: { type: 'success', message: successMessage },
        }));
        const promptCopy = () => window.prompt(promptMessage, value);
        const copyWithSelection = () => {
            if (typeof document.execCommand !== 'function') {
                return false;
            }

            const textarea = document.createElement('textarea');
            const activeElement = document.activeElement;

            textarea.value = value;
            textarea.setAttribute('readonly', '');
            textarea.setAttribute('aria-hidden', 'true');
            textarea.tabIndex = -1;
            textarea.style.position = 'fixed';
            textarea.style.top = '0';
            textarea.style.left = '-9999px';
            textarea.style.width = '1px';
            textarea.style.height = '1px';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            try {
                textarea.focus({ preventScroll: true });
                textarea.select();
                textarea.setSelectionRange(0, textarea.value.length);

                return document.execCommand('copy');
            } catch (error) {
                return false;
            } finally {
                document.body.removeChild(textarea);

                if (activeElement && typeof activeElement.focus === 'function') {
                    try {
                        activeElement.focus({ preventScroll: true });
                    } catch (error) {
                        try {
                            activeElement.focus();
                        } catch (error) {
                        }
                    }
                }
            }
        };
        const copyWithClipboard = () => {
            if (! window.navigator || ! window.navigator.clipboard || ! window.isSecureContext) {
                return Promise.resolve(false);
            }

            return window.navigator.clipboard.writeText(value)
                .then(() => true)
                .catch(() => false);
        };

        copyWithClipboard().then((copied) => {
            if (copied || copyWithSelection()) {
                notifyCopied();

                return;
            }

            promptCopy();
        });
    "
>
    {{ $slot }}
</div>
