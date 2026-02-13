<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\IndicatorCategory;
use App\Models\CategoryField;
use App\Models\AuditLog;
use Illuminate\Validation\Rule;

class CategoryManager extends Component
{
    public $categories = [];
    public ?int $categoryId = null;
    public string $name = '';
    public string $slug = '';
    public ?string $description = null;
    public bool $requires_chapter = false;
    public bool $is_active = true;
    public bool $is_mandatory = true;
    public int $display_order = 0;

    // Field Management Properties
    public bool $showFieldManager = false;
    public ?int $managingCategoryId = null;
    public ?string $managingCategoryName = null;
    public $categoryFields = [];

    // Field Form Properties
    public ?int $fieldId = null;
    public string $fieldName = '';
    public string $fieldLabel = '';
    public string $fieldType = 'text';
    public string $dbColumn = '';
    public array $fieldOptions = [];
    public string $fieldOptionsText = '';
    public bool $fieldRequired = false;
    public int $fieldDisplayOrder = 0;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'alpha_dash',
                'max:255',
                Rule::unique('indicator_categories', 'slug')->ignore($this->categoryId),
            ],
            'description' => ['nullable', 'string'],
            'requires_chapter' => ['boolean'],
            'is_active' => ['boolean'],
            'is_mandatory' => ['boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function mount(): void
    {
        $this->loadCategories();
    }

    public function loadCategories(): void
    {
        $user = auth()->user();
        $this->categories = IndicatorCategory::visibleTo($user)
            ->orderBy('display_order')
            ->orderBy('name')
            ->with('creator')
            ->get();
    }

    public function edit(int $id): void
    {
        $model = IndicatorCategory::findOrFail($id);
        $this->categoryId = $model->id;
        $this->name = $model->name;
        $this->slug = $model->slug;
        $this->description = $model->description;
        $this->requires_chapter = (bool) $model->requires_chapter;
        $this->is_active = (bool) $model->is_active;
        $this->is_mandatory = (bool) $model->is_mandatory;
        $this->display_order = (int) $model->display_order;
    }

    public function resetForm(): void
    {
        $this->reset(['categoryId', 'name', 'slug', 'description', 'requires_chapter', 'is_active', 'is_mandatory', 'display_order']);
        $this->requires_chapter = false;
        $this->is_active = true;
        $this->is_mandatory = true;
        $this->display_order = 0;
    }

    public function save(): void
    {
        $data = $this->validate($this->rules());

        // Set created_by on new records
        if (!$this->categoryId) {
            $data['created_by'] = auth()->id();
        }

        $category = IndicatorCategory::updateOrCreate(
            ['id' => $this->categoryId],
            $data
        );

        // Log category operation
        if ($this->categoryId) {
            // Update - calculate diff
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'update',
                'entity_type' => 'IndicatorCategory',
                'entity_id' => (string)$category->id,
                'changes' => ['diff' => [
                    'name' => ['before' => null, 'after' => $category->name],
                    'slug' => ['before' => null, 'after' => $category->slug],
                ]],
            ]);
        } else {
            // Create
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'create',
                'entity_type' => 'IndicatorCategory',
                'entity_id' => (string)$category->id,
                'changes' => ['diff' => [
                    'name' => ['before' => null, 'after' => $category->name],
                    'slug' => ['before' => null, 'after' => $category->slug],
                ]],
            ]);
        }

        session()->flash('success', 'Category saved successfully.');
        $this->resetForm();
        $this->loadCategories();
    }

    public function delete(int $id): void
    {
        $category = IndicatorCategory::find($id);
        if ($category && $category->slug === 'strategic_plan') {
            session()->flash('error', 'Default categories cannot be deleted.');
            return;
        }

        // Snapshot category before deletion
        $snapshot = $category?->only(['id', 'name', 'slug', 'description', 'is_active', 'is_mandatory', 'display_order']);

        IndicatorCategory::whereKey($id)->delete();

        // Log category deletion
        if ($snapshot) {
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'delete',
                'entity_type' => 'IndicatorCategory',
                'entity_id' => (string)$id,
                'changes' => ['deleted' => $snapshot],
            ]);
        }

        session()->flash('success', 'Category removed.');
        $this->loadCategories();

        if ($this->categoryId === $id) {
            $this->resetForm();
        }
    }

    // Field Management Methods
    public function openFieldManager(int $categoryId): void
    {
        $category = IndicatorCategory::findOrFail($categoryId);
        $this->managingCategoryId = $category->id;
        $this->managingCategoryName = $category->name;
        $this->showFieldManager = true;
        $this->loadCategoryFields();
    }

    public function closeFieldManager(): void
    {
        $this->showFieldManager = false;
        $this->managingCategoryId = null;
        $this->managingCategoryName = null;
        $this->categoryFields = [];
        $this->resetFieldForm();
    }

    public function loadCategoryFields(): void
    {
        if ($this->managingCategoryId) {
            $this->categoryFields = CategoryField::where('category_id', $this->managingCategoryId)
                ->orderBy('display_order')
                ->get();
        }
    }

    public function resetFieldForm(): void
    {
        $this->fieldId = null;
        $this->fieldName = '';
        $this->fieldLabel = '';
        $this->fieldType = 'text';
        $this->dbColumn = '';
        $this->fieldOptions = [];
        $this->fieldOptionsText = '';
        $this->fieldRequired = false;
        $this->fieldDisplayOrder = 0;
    }

    public function editField(int $fieldId): void
    {
        $field = CategoryField::findOrFail($fieldId);
        $this->fieldId = $field->id;
        $this->fieldName = $field->field_name;
        $this->fieldLabel = $field->field_label;
        $this->fieldType = $field->field_type;
        $this->dbColumn = $field->db_column;
        $this->fieldOptions = $field->options ?? [];
        $this->fieldOptionsText = implode("\n", $this->fieldOptions);
        $this->fieldRequired = (bool) $field->is_required;
        $this->fieldDisplayOrder = (int) $field->display_order;
    }

    protected function fieldRules(): array
    {
        return [
            'fieldName' => [
                'required',
                'alpha_dash',
                'max:100',
                Rule::unique('category_fields', 'field_name')
                    ->where('category_id', $this->managingCategoryId)
                    ->ignore($this->fieldId),
            ],
            'fieldLabel' => ['required', 'string', 'max:255'],
            'fieldType' => ['required', Rule::in(['text', 'textarea', 'select', 'number'])],
            'dbColumn' => ['required', 'string', 'max:100'],
            'fieldRequired' => ['boolean'],
            'fieldDisplayOrder' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function saveField(): void
    {
        $this->validate($this->fieldRules());

        // Parse options from text (one per line) for select fields
        $options = null;
        if ($this->fieldType === 'select' && !empty($this->fieldOptionsText)) {
            $options = array_filter(
                array_map('trim', explode("\n", $this->fieldOptionsText))
            );
        }

        $field = CategoryField::updateOrCreate(
            ['id' => $this->fieldId],
            [
                'category_id' => $this->managingCategoryId,
                'field_name' => $this->fieldName,
                'field_label' => $this->fieldLabel,
                'field_type' => $this->fieldType,
                'db_column' => $this->dbColumn,
                'options' => $options,
                'is_required' => $this->fieldRequired,
                'display_order' => $this->fieldDisplayOrder,
                'is_active' => true,
            ]
        );

        // Log field operation
        $action = $this->fieldId ? 'update' : 'create';
        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => 'CategoryField',
            'entity_id' => (string)$field->id,
            'changes' => ['diff' => [
                'field_name' => ['before' => null, 'after' => $field->field_name],
                'field_label' => ['before' => null, 'after' => $field->field_label],
                'field_type' => ['before' => null, 'after' => $field->field_type],
                'category_id' => ['before' => null, 'after' => $field->category_id],
            ]],
        ]);

        session()->flash('field_success', 'Field saved successfully.');
        $this->resetFieldForm();
        $this->loadCategoryFields();
    }

    public function deleteField(int $fieldId): void
    {
        $field = CategoryField::find($fieldId);

        // Snapshot field before deletion
        $snapshot = $field?->only(['id', 'field_name', 'field_label', 'field_type', 'category_id']);

        CategoryField::whereKey($fieldId)->delete();

        // Log field deletion
        if ($snapshot) {
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'delete',
                'entity_type' => 'CategoryField',
                'entity_id' => (string)$fieldId,
                'changes' => ['deleted' => $snapshot],
            ]);
        }

        session()->flash('field_success', 'Field removed.');
        $this->loadCategoryFields();

        if ($this->fieldId === $fieldId) {
            $this->resetFieldForm();
        }
    }

    public function toggleFieldActive(int $fieldId): void
    {
        $field = CategoryField::findOrFail($fieldId);
        $before = $field->is_active;
        $field->update(['is_active' => !$field->is_active]);

        // Log field activation toggle
        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => 'CategoryField',
            'entity_id' => (string)$field->id,
            'changes' => ['diff' => [
                'is_active' => ['before' => $before, 'after' => $field->is_active],
            ]],
        ]);

        $this->loadCategoryFields();
    }

    public function getAvailableColumnsProperty(): array
    {
        return CategoryField::getAvailableColumns();
    }

    public function getFieldTypesProperty(): array
    {
        return CategoryField::getFieldTypes();
    }

    public function render()
    {
        return view('livewire.admin.category-manager');
    }
}
