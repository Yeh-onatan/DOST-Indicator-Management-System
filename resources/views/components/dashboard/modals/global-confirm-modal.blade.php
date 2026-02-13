<div
x-data="{
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    confirmColor: 'red',
    action: null,
    loading: false
}"
@confirm-modal.window="
    show = true;
    title = $event.detail.title;
    message = $event.detail.message;
    confirmText = $event.detail.confirmText || 'Confirm';
    confirmColor = $event.detail.confirmColor || 'red';
    action = $event.detail.action;
"
x-show="show"
x-cloak
class="fixed inset-0 z-[99999] overflow-y-auto"
aria-labelledby="modal-title"
role="dialog"
aria-modal="true"
>
{{-- Backdrop --}}
<div x-show="show"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
    <div x-show="show"
         @click.away="show = false"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">

        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                {{-- Dynamic Icon Color --}}
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
                     :class="confirmColor === 'green' ? 'bg-green-100' : 'bg-red-100'">

                    {{-- Icon for Red/Delete/Reject --}}
                    <svg x-show="confirmColor !== 'green'" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>

                    {{-- Icon for Green/Approve --}}
                    <svg x-show="confirmColor === 'green'" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>

                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <h3 class="text-base font-semibold leading-6 text-gray-900" x-text="title"></h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500" x-text="message"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
            {{-- Dynamic Confirm Button --}}
            <button type="button"
                @click="
                    loading = true;
                    $wire[action.method](action.id).then(() => {
                        show = false;
                        loading = false;
                    });
                "
                :disabled="loading"
                class="inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto disabled:opacity-50 transition"
                :class="confirmColor === 'green' ? 'bg-green-600 hover:bg-green-500' : 'bg-red-600 hover:bg-red-500'">
                <span x-show="!loading" x-text="confirmText"></span>
                <span x-show="loading">Processing...</span>
            </button>

            <button type="button"
                @click="show = false"
                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                Cancel
            </button>
        </div>
    </div>
</div>
</div>
