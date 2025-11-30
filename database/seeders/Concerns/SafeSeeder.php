<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Schema;

/**
 * SafeSeeder Trait
 *
 * ช่วยให้ seeders รันได้อย่างปลอดภัยแม้ตารางบางตารางยังไม่มี
 * ใช้ในกรณีที่ migrations อาจล้มเหลวบางส่วน
 *
 * @package Database\Seeders\Concerns
 */
trait SafeSeeder
{
    /**
     * ตรวจสอบว่าตารางมีอยู่หรือไม่
     *
     * @param string $table ชื่อตาราง
     * @return bool
     */
    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    /**
     * ตรวจสอบหลายตารางพร้อมกัน
     *
     * @param array $tables รายชื่อตาราง
     * @return bool true ถ้าทุกตารางมีอยู่
     */
    protected function tablesExist(array $tables): bool
    {
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }
        return true;
    }

    /**
     * ตรวจสอบว่าตารางพร้อมใช้งาน ถ้าไม่มีให้แจ้งเตือนและ skip
     *
     * @param string $table ชื่อตาราง
     * @param string $seederName ชื่อ seeder (สำหรับแสดงผล)
     * @return bool true ถ้าตารางพร้อมใช้งาน
     */
    protected function requireTable(string $table, string $seederName = ''): bool
    {
        if (!Schema::hasTable($table)) {
            $name = $seederName ?: class_basename($this);
            if (property_exists($this, 'command') && $this->command) {
                $this->command->warn("⚠️  {$name}: ตาราง '{$table}' ยังไม่มี - ข้าม seeder นี้");
            }
            return false;
        }
        return true;
    }

    /**
     * ตรวจสอบหลายตารางที่จำเป็น
     *
     * @param array $tables รายชื่อตาราง
     * @param string $seederName ชื่อ seeder
     * @return bool true ถ้าทุกตารางพร้อมใช้งาน
     */
    protected function requireTables(array $tables, string $seederName = ''): bool
    {
        $missingTables = [];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if (!empty($missingTables)) {
            $name = $seederName ?: class_basename($this);
            $tableList = implode(', ', $missingTables);
            if (property_exists($this, 'command') && $this->command) {
                $this->command->warn("⚠️  {$name}: ตาราง '{$tableList}' ยังไม่มี - ข้าม seeder นี้");
            }
            return false;
        }

        return true;
    }

    /**
     * รัน callback อย่างปลอดภัย พร้อมจัดการ error
     *
     * @param callable $callback ฟังก์ชันที่จะรัน
     * @param string $operationName ชื่อการทำงาน (สำหรับแสดงผล)
     * @return bool true ถ้าสำเร็จ
     */
    protected function safeRun(callable $callback, string $operationName = ''): bool
    {
        try {
            $callback();
            return true;
        } catch (\Exception $e) {
            $name = $operationName ?: 'Operation';
            if (property_exists($this, 'command') && $this->command) {
                $this->command->warn("⚠️  {$name} ล้มเหลว: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * ตรวจสอบว่าคอลัมน์มีอยู่หรือไม่
     *
     * @param string $table ชื่อตาราง
     * @param string $column ชื่อคอลัมน์
     * @return bool
     */
    protected function columnExists(string $table, string $column): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }
        return Schema::hasColumn($table, $column);
    }

    /**
     * นับจำนวนแถวในตารางอย่างปลอดภัย
     *
     * @param string $table ชื่อตาราง
     * @return int จำนวนแถว (0 ถ้าตารางไม่มี)
     */
    protected function safeCount(string $table): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        try {
            return \DB::table($table)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * แสดงข้อความ info อย่างปลอดภัย
     *
     * @param string $message ข้อความ
     * @return void
     */
    protected function info(string $message): void
    {
        if (property_exists($this, 'command') && $this->command) {
            $this->command->info($message);
        }
    }

    /**
     * แสดงข้อความ warn อย่างปลอดภัย
     *
     * @param string $message ข้อความ
     * @return void
     */
    protected function warn(string $message): void
    {
        if (property_exists($this, 'command') && $this->command) {
            $this->command->warn($message);
        }
    }

    /**
     * แสดงข้อความ error อย่างปลอดภัย
     *
     * @param string $message ข้อความ
     * @return void
     */
    protected function error(string $message): void
    {
        if (property_exists($this, 'command') && $this->command) {
            $this->command->error($message);
        }
    }
}
