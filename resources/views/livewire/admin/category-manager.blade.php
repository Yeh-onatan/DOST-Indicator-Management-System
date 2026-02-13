<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold" style="color:#003B5C;">Indicator Categories</h1>
            <p class="text-sm mt-1" style="color:#4B5563;">Create, reorder, and toggle categories available throughout the platform.</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="rounded-lg p-3 text-sm font-medium" style="background:#E5F9FF; border:1px solid #00AEEF; color:#003B5C;">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-lg p-3 text-sm font-medium bg-red-100 border border-red-500 text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 rounded-2xl border border-[var(--border)] bg-[var(--card-bg)] p-5 shadow-sm">
            <h2 class="text-lg font-semibold mb-4" style="color:#003B5C;">
                {{ $categoryId ? 'Edit Category' : 'Create Category' }}
            </h2>
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.defer="name" class="w-full rounded-lg border px-3 py-2" />
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Slug <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.defer="slug" class="w-full rounded-lg border px-3 py-2" placeholder="e.g. agency_specifics" />
                    @error('slug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Description</label>
                    <textarea wire:model.defer="description" rows="3" class="w-full rounded-lg border px-3 py-2"></textarea>
                    @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Display Order</label>
                        <input type="number" min="0" wire:model.defer="display_order" class="w-full rounded-lg border px-3 py-2" />
                        @error('display_order') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-3 pt-6">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.defer="requires_chapter" class="rounded" />
                            Requires chapters
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.defer="is_active" class="rounded" />
                            Active
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.defer="is_mandatory" class="rounded" />
                            Mandatory (visible to all)
                        </label>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-4 py-2 rounded-lg font-semibold text-white" style="background:#00AEEF;">
                        {{ $categoryId ? 'Update' : 'Save' }}
                    </button>
                    @if($categoryId)
                        <button type="button" wire:click="resetForm" class="px-4 py-2 rounded-lg border">Cancel</button>
                    @endif
                </div>
            </form>
        </div>

        <div class="lg:col-span-2 rounded-2xl border border-[var(--border)] bg-[var(--card-bg)] p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold" style="color:#003B5C;">Existing Categories</h2>
                    <p class="text-sm" style="color:#4B5563;">Ordered as they appear in dropdowns and filters.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-[var(--text-muted)] border-b">
                            <th class="py-2 pr-4">Name</th>
                            <th class="py-2 pr-4">Slug</th>
                            <th class="py-2 pr-4">Requires Chapters</th>
                            <th class="py-2 pr-4">Type</th>
                            <th class="py-2 pr-4">Active</th>
                            <th class="py-2 pr-4">Order</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr class="border-b last:border-0">
                                <td class="py-3 pr-4">
                                    <div class="font-semibold text-[var(--text)]">{{ $category->name }}</div>
                                    @if($category->description)
                                        <p class="text-xs text-[var(--text-muted)]">{{ $category->description }}</p>
                                    @endif
                                </td>
                                <td class="py-3 pr-4">{{ $category->slug }}</td>
                                <td class="py-3 pr-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $category->requires_chapter ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $category->requires_chapter ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $category->is_mandatory ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                                        {{ $category->is_mandatory ? 'Mandatory' : 'Non-Mandatory' }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $category->is_active ? 'Active' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">{{ $category->display_order }}</td>
                                <td class="py-3 text-right space-x-2">
                                    <button type="button" wire:click="openFieldManager({{ $category->id }})" class="text-[#00AEEF] hover:underline">Fields</button>
                                    <button type="button" wire:click="edit({{ $category->id }})" class="text-blue-600 hover:underline">Edit</button>
                                    <button type="button" wire:click="delete({{ $category->id }})" class="text-red-600 hover:underline"
                                            @disabled(in_array($category->slug, ['strategic_plan','pdp','prexc','agency_specifics']))>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-[var(--text-muted)]">No categories yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Field Manager Modal --}}
    @if($showFieldManager)
        <div class="fixed inset-0 z-[9999] overflow-y-auto" x-data x-cloak>
            <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" wire:click="closeFieldManager"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-xl shadow-2xl bg-white dark:bg-gray-800">
                    {{-- Header --}}
                    <div class="sticky top-0 z-10 bg-gradient-to-r from-[#003B5C] to-[#00AEEF] px-6 py-4 rounded-t-xl">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white">
                                Manage Fields: {{ $managingCategoryName }}
                            </h3>
                            <button wire:click="closeFieldManager" class="text-white/80 hover:text-white transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        @if(session()->has('field_success'))
                            <div class="rounded-lg p-3 mb-4 text-sm font-medium" style="background:#E5F9FF; border:1px solid #00AEEF; color:#003B5C;">
                                {{ session('field_success') }}
                            </div>
                        @endif

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- Field Form --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                                <h4 class="text-lg font-semibold mb-4" style="color:#003B5C;">
                                    {{ $fieldId ? 'Edit Field' : 'Add New Field' }}
                                </h4>
                                <form wire:submit.prevent="saveField" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Field Name <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="fieldName" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-700 dark:border-gray-600" placeholder="e.g. outcome" />
                                        <p class="text-xs text-gray-500 mt-1">Unique identifier (lowercase, no spaces)</p>
                                        @error('fieldName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Field Label <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="fieldLabel" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-700 dark:border-gray-600" placeholder="e.g. Outcome" />
                                        @error('fieldLabel') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Field Type <span class="text-red-500">*</span></label>
                                            <select wire:model.live="fieldType" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-700 dark:border-gray-600">
                                                @foreach($this->fieldTypes as $type => $label)
                                                    <option value="{{ $type }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('fieldType') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Maps To Column <span class="text-red-500">*</span></label>
                                            <select wire:model.defer="dbColumn" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-700 dark:border-gray-600">
                                                <option value="">Select column...</option>
                                                @foreach($this->availableColumns as $col => $colLabel)
                                                    <option value="{{ $col }}">{{ $colLabel }}</option>
                                                @endforeach
                                            </select>
                                            @error('dbColumn') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    @if($fieldType === 'select')
                                        <div>
                                            <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Options (one per line)</label>
                                            <textarea wire:model.defer="fieldOptionsText" rows="3" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-700 dark:border-gray-600" placeholder="option1&#10;option2&#10;option3"></textarea>
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold mb-1" style="color:#003B5C;">Display Order</label>
                                            <input type="number" min="0" wire:model.defer="fieldDisplayOrder" class="w-full rounded-lg border px-3 py-2 dark:bg-gray-700 dark:border-gray-600" />
                                        </div>
                                        <div class="flex items-center pt-6">
                                            <label class="inline-flex items-center gap-2 text-sm">
                                                <input type="checkbox" wire:model.defer="fieldRequired" class="rounded" />
                                                Required field
                                            </label>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 pt-2">
                                        <button type="submit" class="px-4 py-2 rounded-lg font-semibold text-white" style="background:#00AEEF;">
                                            {{ $fieldId ? 'Update Field' : 'Add Field' }}
                                        </button>
                                        @if($fieldId)
                                            <button type="button" wire:click="resetFieldForm" class="px-4 py-2 rounded-lg border">Cancel</button>
                                        @endif
                                    </div>
                                </form>
                            </div>

                            {{-- Existing Fields List --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                                <h4 class="text-lg font-semibold mb-4" style="color:#003B5C;">Configured Fields</h4>
                                @if(count($categoryFields) > 0)
                                    <div class="space-y-3">
                                        @foreach($categoryFields as $field)
                                            <div class="flex items-center justify-between p-3 rounded-lg {{ $field->is_active ? 'bg-gray-50 dark:bg-gray-700' : 'bg-gray-200 dark:bg-gray-800 opacity-60' }}">
                                                <div>
                                                    <div class="font-semibold text-[var(--text)]">
                                                        {{ $field->field_label }}
                                                        @if($field->is_required)
                                                            <span class="text-red-500">*</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $field->field_name }} &bull; {{ $field->field_type }} &bull; {{ $field->db_column }}
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" wire:click="toggleFieldActive({{ $field->id }})" class="text-xs {{ $field->is_active ? 'text-orange-600' : 'text-green-600' }} hover:underline">
                                                        {{ $field->is_active ? 'Disable' : 'Enable' }}
                                                    </button>
                                                    <button type="button" wire:click="editField({{ $field->id }})" class="text-xs text-blue-600 hover:underline">Edit</button>
                                                    <button type="button" wire:click="deleteField({{ $field->id }})" class="text-xs text-red-600 hover:underline">Delete</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        <p>No fields configured for this category.</p>
                                        <p class="text-sm mt-1">Add fields using the form on the left.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" wire:click="closeFieldManager" class="px-4 py-2 rounded-lg font-semibold text-white" style="background:#003B5C;">
                                Done
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
