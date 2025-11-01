<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 Starting database seeding...');
        $this->command->info('');

        // Seed demo data in order
        $this->call([
            DemoUsersSeeder::class,        // สร้างผู้ใช้ทดสอบ
            DemoAffiliatesSeeder::class,   // สร้าง affiliates
            DemoCommissionsSeeder::class,  // สร้าง commissions
            DemoPagesSeeder::class,        // สร้างหน้าเพจต่างๆ
            SeoMetaSeeder::class,          // สร้าง SEO meta data
            TestUsersSeeder::class,        // สร้างผู้ใช้ทดสอบเพิ่มเติม (backward compatibility)
            EmailTemplateSeeder::class,    // สร้าง Email Templates สำหรับระบบส่งอีเมล
        ]);

        $this->command->info('');
        $this->command->info('✨ Database seeding completed successfully!');
        $this->command->info('');
    }
}
