<?php

namespace App\Services\Workflow;

use App\Models\Privilege\Permission;
use App\Models\Privilege\RolePermissions;
use App\Models\Workflow\RoleStages;
use App\Models\Workflow\WorkflowDefinition;
use App\Models\Workflow\WorkflowStage;
use App\Traits\UserSnapshotTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    use UserSnapshotTrait;

    public function createWorkFlowStage(array $data): WorkflowStage
    {
        $workFlowStage = WorkflowStage::create($data);
        Log::info('Created workflow stage and Id is: ' . $workFlowStage->id);
        $this->createStagePermission($workFlowStage);
        return $workFlowStage;
    }

    public function createWorkFlowDefinition(array $data): WorkflowDefinition
    {
        $workFlowDefinition = WorkflowDefinition::create($data);
        Log::info('Created workflow definition and Id is: ' . $workFlowDefinition->id);
        return $workFlowDefinition;
    }

    public function getAllWorkFlowStages(): Collection
    {
        return WorkflowStage::latest()->get();
    }

    public function getAllWorkFlowDefinitions(): array
    {
        $definitions = WorkflowDefinition::oldest()->get();

        $allStageIds = $definitions->flatMap(fn($d) => $d->workflow_definition)->unique();

        $allStages = WorkflowStage::whereIn('id', $allStageIds)->get()->keyBy('id');

        $result = [];

        foreach ($definitions as $definition) {
            $stages = collect($definition->workflow_definition)
                ->map(fn($id) => $allStages->get($id))
                ->filter()
                ->values();

            $result[] = [
                "id" => $definition->id,
                "workflow_name" => $definition->workflow_name,
                "workflow_stage" => $stages,
            ];
        }

        return $result;
    }

    public function getAllWorkFlowDefinitionWithStages(int $workflow_definition_id): array
    {
        $workflow_definition = WorkflowDefinition::where('id', $workflow_definition_id)->firstOrFail();

        $workflow_stages = WorkflowStage::whereIn('id', $workflow_definition->workflow_definition)
            ->get();

        return [
            "id" => $workflow_definition->id,
            "workflow_name" => $workflow_definition->workflow_name,
            "workflow_stage" => $workflow_stages,
        ];
    }

    public function syncStagesToRole(array $data): void
    {
        $roleId = $data['role_id'];
        $requestedStages = $data['stage_ids'];

        $currentStageIds = RoleStages::where('role_id', $roleId)
            ->where('is_active', true)
            ->pluck('stage_id')
            ->toArray();

        $toAssign = array_diff($requestedStages, $currentStageIds);
        $toUnassign = array_diff($currentStageIds, $requestedStages);
        $skipped = array_intersect($requestedStages, $currentStageIds);

        if (!empty($toUnassign)) {
            RoleStages::where('role_id', $roleId)
                ->whereIn('stage_id', $toUnassign)
                ->update(['is_active' => false, 'updated_by' => $this->getUserSnapshot(),'updated_at' => now()]);
        }

        foreach ($toAssign as $stageId) {
            RoleStages::updateOrCreate(
                [
                    'role_id' => $roleId,
                    'stage_id' => $stageId,
                ],
                [
                    'is_active' => true,
                    'created_by' => $this->getUserSnapshot(),
                    'created_at' => now(),
                ]
            );
        }

        Log::info("Stages synced for role {$roleId}", [
            'assigned' => array_values($toAssign),
            'unassigned' => array_values($toUnassign),
            'skipped' => array_values($skipped),
        ]);
        $this->syncStagePermissionsToRole($roleId, $toAssign, $toUnassign);
    }

    private function createStagePermission(WorkflowStage $workflowStage): void
    {
        $permissionName = 'stage.' . $workflowStage->id;
        $permissionDisplayName = $workflowStage->stage_name . '-Stage ' . ' Permission';


        if (Permission::where('permission_name', $permissionName)->exists()) {
            Log::warning('Permission already exists for stage: ' . $permissionName);
            return;
        }

        Permission::create([
            'permission_name'           => $permissionName,
            'permission_display_name'   => $permissionDisplayName,
            'permission_description'    => 'Auto-generated permission for stage: ' . $workflowStage->stage_name,
            'controller_name'           => 'Stage',
            'is_active'                 => true,
            'api_url'                   => null,
            'method_name'               => null,
            'permission_type_id'        => $workflowStage->permission_type_id ?? 3,
            'created_by'                => $this->getUserSnapshot(),
            'created_at'                => now(),
        ]);

        Log::info('Created permission: ' . $permissionName . ' for stage ID: ' . $workflowStage->id);
    }

    private function syncStagePermissionsToRole(
        int $roleId,
        array $toAssign,
        array $toUnassign = []
    ): void {
        if (!empty($toUnassign)) {
            $unassignPermissionNames = array_map(fn($id) => 'stage.' . $id, $toUnassign);

            $unassignPermissionIds = Permission::whereIn('permission_name', $unassignPermissionNames)
                ->pluck('id')
                ->toArray();

            if (!empty($unassignPermissionIds)) {
                RolePermissions::where('role_id', $roleId)
                    ->whereIn('permission_id', $unassignPermissionIds)
                    ->update([
                        'is_active'  => false,
                        'updated_by' => $this->getUserSnapshot(),
                        'updated_at' => now(),
                    ]);

                Log::info("Stage permissions deactivated for role {$roleId}", [
                    'permission_ids' => $unassignPermissionIds,
                ]);
            }
        }

        // ── Activate permissions for newly assigned stages ──────────────────────
        if (!empty($toAssign)) {
            $assignPermissionNames = array_map(fn($id) => 'stage.' . $id, $toAssign);

            $assignPermissionIds = Permission::whereIn('permission_name', $assignPermissionNames)
                ->pluck('id')
                ->toArray();

            foreach ($assignPermissionIds as $permissionId) {
                RolePermissions::updateOrCreate(
                    [
                        'role_id'       => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'is_active'  => true,
                        'created_by' => $this->getUserSnapshot(),
                        'created_at' => now(),
                    ]
                );
            }

            Log::info("Stage permissions assigned for role {$roleId}", [
                'permission_ids' => $assignPermissionIds,
            ]);
        }
    }

}
