<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmGenealogy;
use App\Models\MlmGlobalSetting;
use App\Models\MlmRebuildTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * MlmTreeRebuildService - จัดการการ rebuild MLM tree structure
 *
 * ฟีเจอร์หลัก:
 * 1. Rebuild Unilevel tree จาก Genealogy (original_sponsor) ตาม width/depth limits
 * 2. Progress tracking สำหรับ resumable processing
 * 3. Batch processing สำหรับข้อมูลขนาดใหญ่
 * 4. Transaction safety และ rollback protection
 *
 * Algorithm:
 * 1. BFS traverse Genealogy tree (ordered by joined_at)
 * 2. แต่ละ node → หาตำแหน่งใน Unilevel ที่ยังว่าง (respecting max_width)
 * 3. อัพเดท unilevel_sponsor_id, unilevel_level, unilevel_path
 * 4. Rebuild MlmGenealogy table สำหรับ unilevel tree
 */
class MlmTreeRebuildService
{
    // Batch size สำหรับ processing
    const BATCH_SIZE = 100;

    // Progress update frequency (ทุก X items)
    const PROGRESS_UPDATE_FREQUENCY = 50;

    /**
     * @var MlmRebuildTask|null
     */
    protected ?MlmRebuildTask $task = null;

    /**
     * @var int จำนวนที่ประมวลผลแล้ว
     */
    protected int $processedCount = 0;

    /**
     * @var int จำนวนที่ล้มเหลว
     */
    protected int $failedCount = 0;

    /**
     * @var Collection เก็บ member ที่อยู่ในแต่ละ level (สำหรับหาตำแหน่งว่าง)
     * Format: ['level_0' => [member1, member2, ...], 'level_1' => [...], ...]
     */
    protected Collection $levelQueues;

    /**
     * @var array นับจำนวน children ของแต่ละ member
     * Format: [member_id => count, ...]
     */
    protected array $childrenCount = [];

    /**
     * เริ่ม rebuild Unilevel tree จาก Genealogy
     *
     * @param int|null $userId ผู้เริ่ม task
     * @return MlmRebuildTask|null
     */
    public function startUnilevelRebuild(?int $userId = null): ?MlmRebuildTask
    {
        // ดึง config ปัจจุบัน
        $config = [
            'unilevel_max_width' => MlmGlobalSetting::get('unilevel_max_width', null),
            'unilevel_max_depth' => MlmGlobalSetting::get('unilevel_max_depth', 10),
            'trigger' => 'manual_rebuild',
            'started_by' => $userId,
        ];

        // สร้าง task พร้อม lock
        $this->task = MlmRebuildTask::createWithLock(
            MlmRebuildTask::TYPE_UNILEVEL_REBUILD,
            $config,
            $userId
        );

        if (!$this->task) {
            Log::warning('Cannot start rebuild - another task is running');
            return null;
        }

        // นับจำนวนสมาชิกทั้งหมด
        $totalMembers = MlmMember::count();

        // เริ่ม task
        $this->task->start($totalMembers);

        Log::info('Unilevel rebuild started', [
            'task_id' => $this->task->id,
            'total_members' => $totalMembers,
            'config' => $config,
        ]);

        return $this->task;
    }

    /**
     * ดำเนินการ rebuild (เรียกจาก Job)
     *
     * @param MlmRebuildTask $task
     * @return bool
     */
    public function executeRebuild(MlmRebuildTask $task): bool
    {
        $this->task = $task;

        // ตรวจสอบว่า task ยังทำงานได้หรือไม่
        if ($task->status !== MlmRebuildTask::STATUS_RUNNING) {
            Log::warning('Task is not in running state', ['task_id' => $task->id, 'status' => $task->status]);
            return false;
        }

        try {
            // เริ่มต้น level queues
            $this->initializeLevelQueues();

            // ดึง config
            $maxWidth = $task->config['unilevel_max_width'] ?? null;
            $maxDepth = $task->config['unilevel_max_depth'] ?? 10;

            // หา Root member(s) - สมาชิกที่ไม่มี original_sponsor
            $rootMembers = MlmMember::whereNull('original_sponsor_id')
                ->orderBy('joined_at')
                ->get();

            // ถ้าไม่มี root → ใช้ member ID 1 (Admin)
            if ($rootMembers->isEmpty()) {
                $rootMembers = MlmMember::where('id', 1)->get();
            }

            // ประมวลผลแต่ละ root tree
            foreach ($rootMembers as $root) {
                // ตั้งค่า root member
                $this->setupRootMember($root);

                // BFS rebuild จาก genealogy
                $this->rebuildFromGenealogy($root, $maxWidth, $maxDepth);
            }

            // Rebuild genealogy table
            $this->rebuildGenealogyTable();

            // Mark completed
            $task->markCompleted();

            Log::info('Unilevel rebuild completed successfully', [
                'task_id' => $task->id,
                'processed' => $this->processedCount,
                'failed' => $this->failedCount,
            ]);

            return true;

        } catch (\Exception $e) {
            $task->markFailed($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'last_processed_id' => $task->last_processed_id,
            ]);

            Log::error('Unilevel rebuild failed', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * เริ่มต้น level queues
     */
    protected function initializeLevelQueues(): void
    {
        $this->levelQueues = collect();
        $this->childrenCount = [];
        $this->processedCount = 0;
        $this->failedCount = 0;
    }

    /**
     * ตั้งค่า root member
     *
     * @param MlmMember $root
     */
    protected function setupRootMember(MlmMember $root): void
    {
        // Root อยู่ level 0
        $root->update([
            'unilevel_sponsor_id' => null,
            'unilevel_level' => 0,
            'unilevel_path' => '',
        ]);

        // เพิ่มเข้า level queue
        $this->addToLevelQueue($root, 0);
        $this->childrenCount[$root->id] = 0;

        $this->processedCount++;
        $this->updateProgress($root->id);
    }

    /**
     * Rebuild จาก Genealogy tree ใช้ BFS
     *
     * @param MlmMember $root
     * @param int|null $maxWidth
     * @param int $maxDepth
     */
    protected function rebuildFromGenealogy(MlmMember $root, ?int $maxWidth, int $maxDepth): void
    {
        // BFS Queue - เก็บ [member, target_level]
        $queue = collect();

        // เริ่มจาก direct referrals ของ root (จาก genealogy)
        $directReferrals = MlmMember::where('original_sponsor_id', $root->id)
            ->orderBy('joined_at')
            ->get();

        foreach ($directReferrals as $member) {
            $queue->push(['member' => $member, 'original_sponsor_id' => $root->id]);
        }

        // BFS processing
        while ($queue->isNotEmpty()) {
            $item = $queue->shift();
            $member = $item['member'];
            $originalSponsorId = $item['original_sponsor_id'];

            // หาตำแหน่งว่างใน Unilevel tree
            $placement = $this->findUnilevelPlacement($originalSponsorId, $maxWidth, $maxDepth);

            if (!$placement) {
                Log::warning('Cannot find placement for member', ['member_id' => $member->id]);
                $this->failedCount++;
                continue;
            }

            // อัพเดท member
            $this->placeMemberInUnilevel($member, $placement);

            // เพิ่ม children ของ member นี้ (จาก genealogy) เข้า queue
            $children = MlmMember::where('original_sponsor_id', $member->id)
                ->orderBy('joined_at')
                ->get();

            foreach ($children as $child) {
                $queue->push(['member' => $child, 'original_sponsor_id' => $member->id]);
            }

            $this->processedCount++;
            $this->updateProgress($member->id);

            // ต่ออายุ lock ทุก batch
            if ($this->processedCount % self::BATCH_SIZE === 0) {
                $this->task->renewLock();
            }
        }
    }

    /**
     * หาตำแหน่งว่างใน Unilevel tree
     *
     * @param int $originalSponsorId ID ของ original sponsor
     * @param int|null $maxWidth
     * @param int $maxDepth
     * @return array|null ['sponsor_id' => int, 'level' => int, 'path' => string]
     */
    protected function findUnilevelPlacement(int $originalSponsorId, ?int $maxWidth, int $maxDepth): ?array
    {
        // ถ้าไม่จำกัด width → วางใต้ original sponsor ได้เลย
        if ($maxWidth === null || $maxWidth === 0) {
            $sponsor = MlmMember::find($originalSponsorId);
            if (!$sponsor) {
                return null;
            }

            $newLevel = $sponsor->unilevel_level + 1;
            if ($newLevel > $maxDepth) {
                return null;
            }

            $this->childrenCount[$originalSponsorId] = ($this->childrenCount[$originalSponsorId] ?? 0) + 1;

            return [
                'sponsor_id' => $sponsor->id,
                'level' => $newLevel,
                'path' => $sponsor->unilevel_path ? $sponsor->unilevel_path . '/' . $sponsor->id : (string) $sponsor->id,
            ];
        }

        // BFS หาตำแหน่งว่าง - เริ่มจาก original sponsor แล้วไปชั้นถัดไป
        $startMember = MlmMember::find($originalSponsorId);
        if (!$startMember) {
            return null;
        }

        // ใช้ BFS queue
        $searchQueue = collect([['member' => $startMember, 'depth' => $startMember->unilevel_level]]);
        $visited = collect([$startMember->id]);

        while ($searchQueue->isNotEmpty()) {
            $item = $searchQueue->shift();
            $current = $item['member'];
            $currentDepth = $item['depth'];

            // ตรวจสอบ depth limit
            if ($currentDepth >= $maxDepth) {
                continue;
            }

            // ตรวจสอบว่า current มีที่ว่างไหม
            $currentChildCount = $this->childrenCount[$current->id] ?? 0;
            if ($currentChildCount < $maxWidth) {
                // มีที่ว่าง!
                $this->childrenCount[$current->id] = $currentChildCount + 1;

                return [
                    'sponsor_id' => $current->id,
                    'level' => $currentDepth + 1,
                    'path' => $current->unilevel_path ? $current->unilevel_path . '/' . $current->id : (string) $current->id,
                ];
            }

            // ไม่มีที่ว่าง → เพิ่ม children เข้า queue
            $children = MlmMember::where('unilevel_sponsor_id', $current->id)
                ->orderBy('joined_at')
                ->get();

            foreach ($children as $child) {
                if (!$visited->contains($child->id)) {
                    $visited->push($child->id);
                    $searchQueue->push(['member' => $child, 'depth' => $currentDepth + 1]);
                }
            }
        }

        return null;
    }

    /**
     * วาง member ใน Unilevel tree
     *
     * @param MlmMember $member
     * @param array $placement
     */
    protected function placeMemberInUnilevel(MlmMember $member, array $placement): void
    {
        $member->update([
            'unilevel_sponsor_id' => $placement['sponsor_id'],
            'unilevel_level' => $placement['level'],
            'unilevel_path' => $placement['path'],
        ]);

        // เพิ่มเข้า level queue
        $this->addToLevelQueue($member, $placement['level']);
        $this->childrenCount[$member->id] = 0;
    }

    /**
     * เพิ่ม member เข้า level queue
     *
     * @param MlmMember $member
     * @param int $level
     */
    protected function addToLevelQueue(MlmMember $member, int $level): void
    {
        $key = 'level_' . $level;
        if (!$this->levelQueues->has($key)) {
            $this->levelQueues->put($key, collect());
        }
        $this->levelQueues->get($key)->push($member);
    }

    /**
     * อัพเดท progress
     *
     * @param int $lastProcessedId
     */
    protected function updateProgress(int $lastProcessedId): void
    {
        if ($this->processedCount % self::PROGRESS_UPDATE_FREQUENCY === 0) {
            $this->task->updateProgress($this->processedCount, $this->failedCount, $lastProcessedId);
        }
    }

    /**
     * Rebuild MlmGenealogy table สำหรับ unilevel tree
     */
    protected function rebuildGenealogyTable(): void
    {
        Log::info('Rebuilding genealogy table...');

        // ลบ unilevel genealogy records เดิม
        MlmGenealogy::where('tree_type', 'unilevel')->delete();

        // สร้างใหม่ทั้งหมด
        $members = MlmMember::whereNotNull('unilevel_sponsor_id')
            ->orderBy('unilevel_level')
            ->get();

        $batch = [];

        foreach ($members as $member) {
            $currentAncestor = $member->unilevelSponsor;
            $depth = 1;

            while ($currentAncestor) {
                $batch[] = [
                    'mlm_member_id' => $member->id,
                    'ancestor_id' => $currentAncestor->id,
                    'mlm_plan_id' => $member->mlm_plan_id,
                    'tree_type' => 'unilevel',
                    'depth' => $depth,
                    'path' => $member->unilevel_path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $currentAncestor = $currentAncestor->unilevelSponsor;
                $depth++;

                if ($depth > 50) {
                    break; // Safety limit
                }
            }

            // Insert in batches
            if (count($batch) >= 1000) {
                MlmGenealogy::insert($batch);
                $batch = [];
            }
        }

        // Insert remaining
        if (!empty($batch)) {
            MlmGenealogy::insert($batch);
        }

        Log::info('Genealogy table rebuilt', ['records_created' => MlmGenealogy::where('tree_type', 'unilevel')->count()]);
    }

    /**
     * อัพเดทสถิติหลัง rebuild
     */
    public function updateStatisticsAfterRebuild(): void
    {
        Log::info('Updating member statistics...');

        // อัพเดท total_team_members
        $members = MlmMember::orderBy('unilevel_level', 'desc')->get();

        foreach ($members as $member) {
            // นับ unilevel children (recursive)
            $teamCount = $this->countAllDownline($member->id);

            $member->update([
                'total_team_members' => $teamCount,
            ]);
        }

        Log::info('Statistics updated');
    }

    /**
     * นับ downline ทั้งหมด (recursive)
     *
     * @param int $memberId
     * @return int
     */
    protected function countAllDownline(int $memberId): int
    {
        $count = 0;
        $children = MlmMember::where('unilevel_sponsor_id', $memberId)->get();

        foreach ($children as $child) {
            $count++;
            $count += $this->countAllDownline($child->id);
        }

        return $count;
    }

    /**
     * ดึงสถานะของ task ล่าสุด
     *
     * @param string $type
     * @return array|null
     */
    public function getLatestTaskStatus(string $type = MlmRebuildTask::TYPE_UNILEVEL_REBUILD): ?array
    {
        $task = MlmRebuildTask::getLatestTask($type);

        if (!$task) {
            return null;
        }

        return $task->getProgressData();
    }

    /**
     * ตรวจสอบว่ามี task กำลังทำงานอยู่หรือไม่
     *
     * @param string $type
     * @return bool
     */
    public function hasRunningTask(string $type = MlmRebuildTask::TYPE_UNILEVEL_REBUILD): bool
    {
        return MlmRebuildTask::hasRunningTask($type);
    }
}
