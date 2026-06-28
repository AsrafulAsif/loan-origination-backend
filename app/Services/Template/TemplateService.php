<?php

namespace App\Services\Template;

use App\Models\Template\FormTemplates;
use App\Models\Template\FormTemplateDetails;
use App\Models\Template\Sections;
use App\Models\Template\Fields;
use App\Models\Template\FieldOptions;
use App\Models\Template\FieldGroups;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Services\Product\ProductService;
use Illuminate\Support\Facades\Log;

class TemplateService
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }


    /**
     * @throws Throwable
     */
    public function create(array $data): FormTemplates
    {
        return DB::transaction(function () use ($data) {

            // Get last version for this product
            $lastVersion = FormTemplates::where('product_id', $data['productId'])
                ->where('status', 'PUBLISHED')
                ->max('version');

            // If no template exists yet, start with version 1
            $newVersion = ($lastVersion ?? 0) + 1;

            $existingTemplate = FormTemplates::where('id', $data['id'])
                ->orderByDesc('version')
                ->first();

            if ($existingTemplate) {
                $existingTemplate->update([
                    'product_id' => $data['productId'],
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'status' => "PUBLISHED",
                    'version' => $newVersion,
                ]);
            } else {
                $existingTemplate = FormTemplates::create([
                    'product_id' => $data['productId'],
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'version' => $newVersion,
                    'status' => "PUBLISHED",
                ]);
            }

            // Create template details
            $existingTemplateDetails = FormTemplateDetails::where('template_id', $data['id'])
                ->first();

            if ($existingTemplateDetails) {
                $existingTemplateDetails->update([
                    'template_json' => $data,
                    'settings_json' => $data['settings'],
                ]);
            } else {
                FormTemplateDetails::create([
                    'template_id' => $existingTemplate->id,
                    'settings_json' => $data['settings'],
                    'template_json' => $data,
                ]);
            }

            // Create Sections
            foreach ($data['sections'] as $sectionData) {

                $section = Sections::create([
                    'template_id' => $existingTemplate->id,
                    'title' => $sectionData['title'],
                    'description' => $sectionData['description'] ?? null,
                    'section_order' => $sectionData['order'],
                    'section_permissions_json' => $sectionData['sectionPermissions'] ?? null,
                    'columns' => $sectionData['columns'],
                    'conditional_logic' => $sectionData['conditionalLogic'] ?? null,
                    'enabled' => $sectionData['enabled'] ?? true,
                    'is_collapsible' => $sectionData['isCollapsible'] ?? false,
                    'section_key' => $sectionData['sectionKey'],
                ]);

                // Create Field Groups
                if (!empty($sectionData['fieldGroups'])) {
                    foreach ($sectionData['fieldGroups'] as $groupData) {

                        $group = FieldGroups::create([
                            'section_id' => $section->id,
                            'group_order' => $groupData['order'] ?? 0,
                            'title' => $groupData['title'],
                            'layout' => $groupData['layout'] ?? null,
                            'columns' => $groupData['columns'] ?? 12,
                            'repeatable' => $groupData['repeatable'] ?? false,
                            'min_instances' => $groupData['minInstances'] ?? null,
                            'max_instances' => $groupData['maxInstances'] ?? null,
                            'group_key' => $groupData['fieldGroupKey'],
                            'section_key' => $sectionData['sectionKey'],

                        ]);

                        // Create Fields inside Group
                        if (!empty($groupData['fields'])) {
                            foreach ($groupData['fields'] as $fieldData) {

                                $field = Fields::create([
                                    'section_id' => $section->id,
                                    'group_id' => $group->id,
                                    'label' => $fieldData['label'],
                                    'field_type' => $fieldData['type'],
                                    'required' => $fieldData['required'] ?? false,
                                    'placeholder' => $fieldData['placeholder'] ?? null,
                                    'help_text' => $fieldData['helpText'] ?? null,
                                    'col_span' => $fieldData['colSpan'] ?? 12,
                                    'field_order' => $fieldData['order'] ?? 0,
                                    'validation_json' => $fieldData['validation'] ?? null,
                                    'table_config' => $fieldData['tableConfig'] ?? null,
                                    'calculated_config' => $fieldData['calculatedConfig'] ?? null,
                                    'conditional_logic' => $fieldData['conditionalLogic'] ?? null,
                                    'enabled' => $fieldData['enabled'] ?? true,
                                    'read_only' => $fieldData['readOnly'] ?? false,
                                    'api_trigger_json' => $fieldData['apiTrigger'] ?? null,
                                    'field_key' => $fieldData['fieldKey'],
                                    'section_key' => $sectionData['sectionKey'],
                                    'multipleFiles' => $fieldData['multipleFiles'] ?? false,
                                ]);

                                // Create Field Options
                                if (!empty($fieldData['options'])) {
                                    foreach ($fieldData['options'] as $index => $option) {
                                        FieldOptions::create([
                                            'field_id' => $field->id,
                                            'option_label' => $option['label'],
                                            'option_value' => $option['value'],
                                            'option_order' => $index,
                                            'field_key' => $field->field_key,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }

                // Create Standalone Fields (no group)
                if (!empty($sectionData['fields'])) {
                    foreach ($sectionData['fields'] as $fieldData) {

                        $field = Fields::create([
                            'section_id' => $section->id,
                            'label' => $fieldData['label'],
                            'field_type' => $fieldData['type'],
                            'required' => $fieldData['required'] ?? false,
                            'placeholder' => $fieldData['placeholder'] ?? null,
                            'help_text' => $fieldData['helpText'] ?? null,
                            'col_span' => $fieldData['colSpan'] ?? 12,
                            'field_order' => $fieldData['order'] ?? 0,
                            'validation_json' => $fieldData['validation'] ?? null,
                            'table_config' => $fieldData['tableConfig'] ?? null,
                            'calculated_config' => $fieldData['calculatedConfig'] ?? null,
                            'conditional_logic' => $fieldData['conditionalLogic'] ?? null,
                            'enabled' => $fieldData['enabled'] ?? true,
                            'read_only' => $fieldData['readOnly'] ?? false,
                            'api_trigger_json' => $fieldData['apiTrigger'] ?? null,
                            'field_key' => $fieldData['fieldKey'],
                            'section_key' => $sectionData['sectionKey'],
                            'multipleFiles' => $fieldData['multipleFiles'] ?? false,
                        ]);

                        // Create Field Options
                        if (!empty($fieldData['options'])) {
                            foreach ($fieldData['options'] as $index => $option) {
                                FieldOptions::create([
                                    'field_id' => $field->id,
                                    'option_label' => $option['label'],
                                    'option_value' => $option['value'],
                                    'option_order' => $index,
                                    'field_key' => $field->field_key,

                                ]);
                            }
                        }
                    }
                }
            }

            Log::info("Template published successfully with id: {$existingTemplate->id} by the employee: " . auth()->user()->employee_id);
            return $existingTemplate;
        });
    }

    /**
     * @throws Throwable
     */
    public function publishTemplate(int $templateId): FormTemplates
    {
        return DB::transaction(function () use ($templateId) {

            $data = $this->getTemplateById($templateId);

            return $this->create($data);
        });
    }

    /**
     * @throws Throwable
     */
    public function duplicateTemplate(int $templateId): int
    {
        return DB::transaction(function () use ($templateId) {

            $data = $this->getTemplateById($templateId);

            // Generate new template title
            $template = FormTemplates::find($templateId);
            $data['title'] = $template->title . ' (Copy)';

            return $this->createDraft($data);
        });
    }

    /**
     * @throws Throwable
     */

    public function deleteDraftTemplate(int $templateId): bool
    {
        // delete only if the status = 'DRAFT'
        if (FormTemplates::where('id', $templateId)->where('status', 'DRAFT')->exists()) {
            DB::transaction(function () use ($templateId) {
                FormTemplates::where('id', $templateId)->delete();
            });
            return true;
        } else {
            // Log the attempt but don't throw an exception
            Log::warning("Attempt to delete non-DRAFT template: {$templateId}");
            return false;
        }
    }

    public function duplicateTemplateDetails(int $templateId): int
    {
        return DB::transaction(function () use ($templateId) {

            $data = $this->getTemplateById($templateId);

            // Generate new template title
            $template = FormTemplates::find($templateId);
            $data['title'] = $template->title . ' (Copy)';
            Log::info("Duplicating template: {$templateId}");
            $newTemplateId = $this->createDraft($data);
            Log::info("Duplicated template: new={$newTemplateId}, source={$templateId}");
            return $newTemplateId;
        });
    }

    /**
     * @throws Throwable
     */
    public function getAllTemplates(): array
    {
        $result = [];

        $templates = FormTemplates::orderBy('id')->get();

        foreach ($templates as $template) {

            // get product
            $productData = $this->productService->getProductWithProductDetails($template->product_id);

            $product = $productData['product'] ?? null;
            $productDetails = $productData['productDetails'] ?? [];

            // Get Template Details
            $details = FormTemplateDetails::where('template_id', $template->id)->first();

            $templateArr = [
                'id' => $template->id,
                'templateId' => $template->id,
                'productId' => $template->product_id,

                // Product info
                'productName' => $product->product_name ?? null,
                'productCode' => $product->product_code ?? null,
                'productDetails' => $productDetails,

                'title' => $template->title,
                'description' => $template->description,
                'version' => $template->version,
                'status' => $template->status,
                'settings' => $details->settings_json ?? null,
                'sections' => [],
            ];

            // Sections
            $sections = Sections::where('template_id', $template->id)
                ->orderBy('section_order')
                ->get();

            foreach ($sections as $section) {

                $sectionArr = [
                    'id' => $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'order' => $section->section_order,
                    'sectionPermissions' => $section->section_permissions_json,
                    'columns' => $section->columns,
                    'conditionalLogic' => $section->conditional_logic,
                    'enabled' => (bool) $section->enabled,
                    'isCollapsible' => $section->is_collapsible,
                    'fieldGroups' => [],
                    'fields' => [],
                ];

                // Field Groups
                $groups = FieldGroups::where('section_id', $section->id)
                    ->orderBy('group_order')
                    ->get();

                foreach ($groups as $group) {

                    $groupArr = [
                        'id' => $group->id,
                        'order' => $group->group_order,
                        'title' => $group->title,
                        'layout' => $group->layout,
                        'columns' => $group->columns,
                        'repeatable' => $group->repeatable,
                        'minInstances' => $group->min_instances,
                        'maxInstances' => $group->max_instances,
                        'fields' => [],
                    ];

                    $fields = Fields::where('group_id', $group->id)
                        ->orderBy('field_order')
                        ->get();

                    foreach ($fields as $field) {
                        $groupArr['fields'][] = $this->buildFieldArray($field);
                    }

                    $sectionArr['fieldGroups'][] = $groupArr;
                }

                // Standalone Fields
                $standaloneFields = Fields::where('section_id', $section->id)
                    ->whereNull('group_id')
                    ->orderBy('field_order')
                    ->get();

                foreach ($standaloneFields as $field) {
                    $sectionArr['fields'][] = $this->buildFieldArray($field);
                }

                $templateArr['sections'][] = $sectionArr;
            }

            $result[] = $templateArr;
        }
        Log::info("Total templates loaded: " . count($result) . " by the employee: " . auth()->user()->employee_id);
        return $result;
    }


    /**
     * @throws Throwable
     */

    public function getTemplateById(int $templateId): ?array
    {
        $template = FormTemplates::where('id', $templateId)->first();
        // Get Template Details
        $details = FormTemplateDetails::where('template_id', $template->id)->first();
        /*
        If full JSON stored → return directly
        For temp use i asif comment this 2 line
        if ($details && !empty($details->template_json)) {
            return $details->template_json;
        }*/
        if ($template->status == 'DRAFT') {
            return $details->template_json;
        }

        // Otherwise rebuild manually
        $result = [
            'id' => $template->id,
            'productId' => $template->product_id,
            'title' => $template->title,
            'description' => $template->description,
            'version' => $template->version,
            'status' => $template->status,
            'settings' => $details->settings_json ?? null,
            'sections' => [],
        ];

        // Get Sections
        $sections = Sections::where('template_id', $template->id)
            ->orderBy('section_order')
            ->get();

        foreach ($sections as $section) {

            $sectionArr = [
                'id' => $section->id,
                'sectionKey' => $section->section_key,
                'title' => $section->title,
                'description' => $section->description,
                'order' => $section->section_order,
                'sectionPermissions' => $section->section_permissions_json,
                'columns' => $section->columns,
                'conditionalLogic' => $section->conditional_logic,
                'enabled' => (bool) $section->enabled,
                'isCollapsible' => $section->is_collapsible,
                'fieldGroups' => [],
                'fields' => [],
            ];

            // Field Groups
            $groups = FieldGroups::where('section_id', $section->id)
                ->orderBy('group_order')
                ->get();

            foreach ($groups as $group) {

                $groupArr = [
                    'id' => $group->id,
                    'fieldGroupKey' => $group->group_key,
                    'order' => $group->group_order,
                    'title' => $group->title,
                    'layout' => $group->layout,
                    'columns' => $group->columns,
                    'repeatable' => $group->repeatable,
                    'minInstances' => $group->min_instances,
                    'maxInstances' => $group->max_instances,
                    'enabled' => (bool) $group->enabled,
                    'fields' => [],
                ];

                $fields = Fields::where('group_id', $group->id)
                    ->orderBy('field_order')
                    ->get();

                foreach ($fields as $field) {
                    $groupArr['fields'][] = $this->buildFieldArray($field);
                }

                $sectionArr['fieldGroups'][] = $groupArr;
            }

            // Standalone Fields
            $standaloneFields = Fields::where('section_id', $section->id)
                ->whereNull('group_id')
                ->orderBy('field_order')
                ->get();

            foreach ($standaloneFields as $field) {
                $sectionArr['fields'][] = $this->buildFieldArray($field);
            }

            $result['sections'][] = $sectionArr;
        }
        Log::info("Template loaded successfully with id: {$result['id']} by the employee: " . auth()->user()->employee_id);
        return $result;
    }

    /**
     * @throws Throwable
     */
    /**
     * Get template by templateId (latest version) for user
     */
    public function getUpdatedTemplateByProductId(int $productId, ?int $templateId): ?array
    {
        // Get latest version of this template
        $template = new FormTemplates();

        if ($productId) {
            $template = FormTemplates::where('product_id', $productId)
                ->where('status', 'PUBLISHED')
                ->orderByDesc('version')
                ->firstOrFail();
        }

        if ($templateId) {
            $template = FormTemplates::where('id', $templateId)
                ->firstOrFail();
        }


        // Get Template Details
        $details = FormTemplateDetails::where('template_id', $template->id)->first();
        /*
        If full JSON stored → return directly
        For temp use i asif comment this 2 line
        if ($details && !empty($details->template_json)) {
            return $details->template_json;
        }
*/

        // Otherwise rebuild manually
        $result = [
            'id' => $template->id,
            'productId' => $template->product_id,
            'title' => $template->title,
            'description' => $template->description,
            'version' => $template->version,
            'status' => $template->status,
            'settings' => $details->settings_json ?? null,
            'sections' => [],
        ];

        // Get Sections
        $sections = Sections::where('template_id', $template->id)
            ->orderBy('section_order')
            ->get();

        foreach ($sections as $section) {

            $sectionArr = [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'order' => $section->section_order,
                'sectionPermissions' => $section->section_permissions_json,
                'sectionKey' => $section->section_key,
                'columns' => $section->columns,
                'conditionalLogic' => $section->conditional_logic,
                'enabled' => $section->enabled,
                'isCollapsible' => $section->is_collapsible,
                'fieldGroups' => [],
                'fields' => [],
            ];

            // Field Groups
            $groups = FieldGroups::where('section_id', $section->id)
                ->orderBy('group_order')
                ->get();

            foreach ($groups as $group) {

                $groupArr = [
                    'id' => $group->id,
                    'fieldGroupKey' => $group->group_key,
                    'order' => $group->group_order,
                    'title' => $group->title,
                    'layout' => $group->layout,
                    'columns' => $group->columns,
                    'repeatable' => $group->repeatable,
                    'minInstances' => $group->min_instances,
                    'maxInstances' => $group->max_instances,
                    'fields' => [],
                ];

                $fields = Fields::where('group_id', $group->id)
                    ->orderBy('field_order')
                    ->get();

                foreach ($fields as $field) {
                    $groupArr['fields'][] = $this->buildFieldArray($field);
                }

                $sectionArr['fieldGroups'][] = $groupArr;
            }

            // Standalone Fields
            $standaloneFields = Fields::where('section_id', $section->id)
                ->whereNull('group_id')
                ->orderBy('field_order')
                ->get();

            foreach ($standaloneFields as $field) {
                $sectionArr['fields'][] = $this->buildFieldArray($field);
            }

            $result['sections'][] = $sectionArr;
        }
        Log::info("Template loaded successfully with id: {$result['id']} by the employee: " . auth()->user()->employee_id);
        return $result;
    }


    /**
     * @throws Throwable
     */
    /**
     * Build field array
     */
    private function buildFieldArray($field): array
    {
        $fieldArr = [
            'id' => $field->id,
            'label' => $field->label,
            'type' => $field->field_type,
            'required' => (bool) $field->required,
            'placeholder' => $field->placeholder,
            'helpText' => $field->help_text,
            'colSpan' => $field->col_span,
            'order' => $field->field_order,
            'validation' => $field->validation_json,
            'tableConfig' => $field->table_config,
            'calculatedConfig' => $field->calculated_config,
            'conditionalLogic' => $field->conditional_logic,
            'enabled' => (bool) $field->enabled,
            'readOnly' => (bool) $field->read_only,
            'apiTrigger' => $field->api_trigger_json,
            'options' => [],
            'fieldKey' => $field->field_key,
            'multipleFiles' => $field->multiple_files,
        ];

        // Fetch options manually
        $options = FieldOptions::where('field_id', $field->id)
            ->orderBy('option_order')
            ->get();

        foreach ($options as $option) {
            $fieldArr['options'][] = [
                'label' => $option->option_label,
                'value' => $option->option_value,
            ];
        }

        return $fieldArr;
    }


    /**
     * @throws Throwable
     */
    public function getAllTemplatesForDashboard(): array
    {
        $result = [];

        $templates = FormTemplates::orderBy('id')->get();

        foreach ($templates as $template) {
            $templateArr = [
                'id' => $template->id,
                'title' => $template->title,
                'status' => $template->status,
                'description' => $template->description,
                'version' => $template->version,
                'created_by' => $template->createdBy,
                'last_updated' => $template->updated_at ? $template->updated_at : $template->created_at,
            ];
            $result[] = $templateArr;
        }
        Log::info("Templates loaded successfully for dashboard. Total templates: " . count($result) . " by the employee: " . auth()->user()?->employee_id);
        return $result;
    }


    /**
     * @throws Throwable
     */
    public function createDraft(array $data): int
    {
        return DB::transaction(function () use ($data) {

            // Get last version for this product
//            $lastVersion = FormTemplates::where('product_id', $data['productId'])
//                ->where('status', 'PUBLISHED')
//                ->max('version');

            // If no template exists yet, start with version 1
            //$newVersion = $lastVersion ? $lastVersion + 1 : 1;

            // Create template
            $template = FormTemplates::create([
                'product_id' => $data['productId'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'version' => null,
                'status' => "DRAFT",
            ]);

            $data = array_merge($data, [
                'id' => $template->id,
                'version' => null
            ]);

            // Create template details
            FormTemplateDetails::create([
                'template_id' => $template->id,
                'settings_json' => $data['settings'],
                'template_json' => $data,
            ]);

            Log::info(
                "Draft Template created successfully with id : {$template->id} by the employee: " .
                auth()->user()?->employee_id
            );

            return $template->id;
        });
    }


    /**
     * @throws Throwable
     */
    public function updateDraft(array $data): ?array
    {
        return DB::transaction(function () use ($data) {

            // Find existing template
            $templateDetails = FormTemplateDetails::where('template_id', $data['id'])
                ->firstOrFail();

            // update template_json field
            $templateDetails->update([
                'template_json' => $data
            ]);

            $response = array_merge($data, ['id' => $templateDetails->template_id]);
            Log::info("Draft Template updated successfully with id : " . $response['id'] . " by the employee: " . auth()->user()?->employee_id);
            return $response;
        });
    }
}
