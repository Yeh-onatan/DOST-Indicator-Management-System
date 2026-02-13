@props([
    'importFile' => null,
    'importErrors' => [],
    'importedCount' => 0,
    'importing' => false,
])

<div x-data="{
    showImportModal: false,
    resetModal() {
        $wire.set('importFile', null);
        $wire.set('importErrors', []);
        $wire.set('importedCount', 0);
    },
    get fileName() {
        const input = document.getElementById('file-upload-alt');
        return input && input.files.length > 0 ? input.files[0].name : null;
    }
}"
    @keydown.escape.window="showImportModal = false; resetModal()">
    <button @click="showImportModal = true" class="fixed bottom-6 right-6 z-[999999] p-3 bg-[#003B5C] text-white rounded-full shadow-lg hover:scale-110 transition-transform group">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <span class="absolute right-full mr-3 top-1/2 -translate-y-1/2 bg-gray-900 text-white text-sm px-3 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
            Import CSV
        </span>
    </button>

    {{-- Import Modal --}}
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-[999999]">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" style="z-index: 1;"></div>
        <div class="flex items-center justify-center p-4 min-h-screen" style="position: relative; z-index: 2;">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6" @click.away="showImportModal = false; resetModal()">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-[#003B5C]">Import Indicators from CSV</h3>
                    <button @click="showImportModal = false; resetModal()" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="border-2 rounded-lg p-8 text-center transition relative" :class="fileName ? 'border-solid border-green-400 bg-green-50' : 'border-dashed border-gray-300 hover:border-[#00AEEF]'">
                        <input id="file-upload-alt" type="file" wire:model="importFile" accept=".csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div x-show="!fileName" style="display: none;">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-[#00AEEF] font-semibold text-lg">Choose a CSV file</p>
                            <p class="text-gray-500">or drag and drop</p>
                        </div>
                        <div x-show="fileName" style="display: none;">
                            <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <p class="text-lg font-semibold text-green-700 mb-1" x-text="fileName"></p>
                            <p class="text-xs text-green-500 mt-2">Click or drag to change file</p>
                        </div>
                    </div>
                    {{-- Loading State --}}
                    <div x-show="fileName && ($importing || $importing)" style="display: none;" class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center gap-3">
                        <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-blue-700">Importing indicators, please wait...</p>
                    </div>
                    {{-- Results (only show after import completes, not during upload) --}}
                    @if($importedCount > 0 && !$importing)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-green-800 font-semibold">Successfully imported {{ $importedCount }} records</p>
                        </div>
                    @endif
                    @if($importedCount === 0 && count($importErrors) > 0 && !$importing)
                        <div class="bg-red-50 border border-red-300 rounded-lg p-4 flex items-center gap-3">
                            <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <p class="text-red-800 font-bold text-base">Import Failed</p>
                                <p class="text-red-600 text-sm">No rows were imported. {{ count($importErrors) }} error(s) found.</p>
                            </div>
                        </div>
                    @endif
                    @if(count($importErrors) > 0 && !$importing)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-red-800 font-semibold mb-2">
                                @if($importedCount > 0)
                                    Import completed with {{ count($importErrors) }} error(s):
                                @else
                                    Errors found:
                                @endif
                            </p>
                            <div class="max-h-48 overflow-y-auto space-y-1">
                                @foreach($importErrors as $error)
                                    <div class="text-sm text-red-600 break-words">• {{ Str::limit($error, 200) }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showImportModal = false; resetModal()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Cancel</button>
                    <button wire:click="importIndicators" :disabled="!fileName || $importing" class="px-6 py-2 rounded-lg font-bold transition-colors" :class="fileName ? 'bg-[#00AEEF] text-white hover:bg-[#008FCC]' : 'bg-gray-300 text-gray-500 cursor-not-allowed'">Import</button>
                </div>
            </div>
        </div>
    </div>
</div>
