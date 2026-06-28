<?php

namespace App\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;

class TemplateCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // Basic Template Info
            'id' => ['required', 'string', 'max:255'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:DRAFT,PUBLISHED,ARCHIVED'],

            // Settings
            'settings' => ['required', 'array'],
            'settings.allowSaveDraft' => ['boolean'],
            'settings.requireAllSections' => ['boolean'],
            'settings.notifyOnSubmit' => ['boolean'],
            'settings.defaultGridColumns' => ['integer'],
            'settings.enableCalculations' => ['boolean'],
            'settings.enableConditionalLogic' => ['boolean'],
            'settings.autoSaveInterval' => ['integer', 'min:0'],
            'settings.validationMode' => ['nullable', 'in:onChange,onSubmit'],

            // Layout
            'settings.layout' => ['nullable', 'array'],
            'settings.layout.type' => ['nullable', 'in:wizard,tabs,vertical'],
            'settings.layout.showSidebar' => ['boolean'],
            'settings.layout.sidebarCollapsible' => ['boolean'],
            'settings.layout.sidebarDefaultCollapsed' => ['boolean'],
            'settings.layout.groupByStage' => ['boolean'],
            'settings.layout.wizardShowProgress' => ['boolean'],
            'settings.layout.wizardShowStepNumbers' => ['boolean'],
            'settings.layout.wizardValidateOnNext' => ['boolean'],
            'settings.layout.tabsPosition' => ['nullable', 'in:top,bottom,left,right'],
            'settings.layout.verticalAllowCollapseAll' => ['boolean'],


            // Sections
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => ['required', 'string'],
            'sections.*.title' => ['required', 'string'],
            'sections.*.description' => ['nullable', 'string'],
            'sections.*.order' => ['required', 'integer'],
            'sections.*.columns' => ['required', 'integer'],
            'sections.*.enabled' => ['boolean'],
            'sections.*.isCollapsible' => ['boolean'],

            // Section Permissions
            'sections.*.sectionPermissions' => ['nullable', 'array'],
            'sections.*.sectionPermissions.*.stageId' => ['required_with:sections.*.sectionPermissions', 'integer'],
            'sections.*.sectionPermissions.*.access' => ['required_with:sections.*.sectionPermissions', 'in:view,edit'],

            // Workflow
            'sections.*.workflow' => ['nullable', 'array'],
            'sections.*.workflow.enabled' => ['boolean'],
            'sections.*.workflow.approversRequired' => ['integer', 'min:0'],


            // Field Groups
            'sections.*.fieldGroups' => ['nullable', 'array'],
            'sections.*.fieldGroups.*.id' => ['required', 'string', 'max : 255'],
            'sections.*.fieldGroups.*.title' => ['required', 'string'],
            'sections.*.fieldGroups.*.layout' => ['nullable', 'string'],
            'sections.*.fieldGroups.*.columns' => ['integer'],
            'sections.*.fieldGroups.*.repeatable' => ['boolean'],
            'sections.*.fieldGroups.*.minInstances' => ['integer'],
            'sections.*.fieldGroups.*.maxInstances' => ['integer'],
            'sections.*.fieldGroups.*.order' => ['integer'],


            // Fields
            'sections.*.fields' => ['nullable', 'array'],
            'sections.*.fields.*.id' => ['required', 'string', 'max : 255'],
            'sections.*.fields.*.label' => ['nullable', 'string'],
            'sections.*.fields.*.type' => ['required', 'string'],
            'sections.*.fields.*.required' => ['boolean'],
            'sections.*.fields.*.placeholder' => ['nullable', 'string'],
            'sections.*.fields.*.order' => ['integer'],
            'sections.*.fields.*.colSpan' => ['integer'],
            'sections.*.fields.*.enabled' => ['boolean'],
            'sections.*.fields.*.readOnly' => ['boolean'],

            // Field Validation Rules
            'sections.*.fields.*.validation' => ['nullable', 'array'],
            'sections.*.fields.*.validation.min' => ['nullable', 'numeric'],
            'sections.*.fields.*.validation.max' => ['nullable', 'numeric'],
            'sections.*.fields.*.validation.minLength' => ['nullable', 'integer'],
            'sections.*.fields.*.validation.maxLength' => ['nullable', 'integer'],

            // Options (select fields)
            'sections.*.fields.*.options' => ['nullable', 'array'],
            'sections.*.fields.*.options.*.label' => ['nullable', 'string'],
            'sections.*.fields.*.options.*.value' => ['required_with:sections.*.fields.*.options'],

            // Calculated Fields
            'sections.*.fields.*.calculatedConfig' => ['nullable', 'array'],
            'sections.*.fields.*.calculatedConfig.formula' => ['required_with:sections.*.fields.*.calculatedConfig', 'string'],
            'sections.*.fields.*.calculatedConfig.dependencies' => ['nullable', 'array'],

            // API Trigger
            'sections.*.fields.*.apiTrigger' => ['nullable', 'array'],
            'sections.*.fields.*.apiTrigger.enabled' => ['boolean'],
            'sections.*.fields.*.apiTrigger.triggerOn' => ['nullable', 'in:blur,change'],
            'sections.*.fields.*.apiTrigger.endpoint' => ['nullable', 'string'],
            'sections.*.fields.*.apiTrigger.method' => ['nullable', 'in:GET,POST'],
            'sections.*.fields.*.apiTrigger.fieldMappings' => ['nullable', 'array'],
            'sections.*.fields.*.apiTrigger.fieldMappings.*.responseKey' => ['required_with:sections.*.fields.*.apiTrigger.fieldMappings', 'string'],
            'sections.*.fields.*.apiTrigger.fieldMappings.*.targetFieldId' => ['required_with:sections.*.fields.*.apiTrigger.fieldMappings', 'string'], // <-- field mapping targets are integers

            // Table Config
            'sections.*.fields.*.tableConfig' => ['nullable', 'array'],
            'sections.*.fields.*.tableConfig.columns' => ['nullable', 'array'],
            'sections.*.fields.*.tableConfig.columns.*.id' => ['required_with:sections.*.fields.*.tableConfig.columns', 'string'], // <-- table column ids as integers
            'sections.*.fields.*.tableConfig.columns.*.header' => ['required_with:sections.*.fields.*.tableConfig.columns', 'string'],
            'sections.*.fields.*.tableConfig.columns.*.type' => ['required_with:sections.*.fields.*.tableConfig.columns', 'string'],

            // Conditional Logic
            'sections.*.fields.*.conditionalLogic' => ['nullable', 'array'],
            'sections.*.fields.*.conditionalLogic.action' => ['required_with:sections.*.fields.*.conditionalLogic', 'in:show,hide,enable,disable'],
            'sections.*.fields.*.conditionalLogic.conditions' => ['nullable', 'array'],
        ];
    }
}
