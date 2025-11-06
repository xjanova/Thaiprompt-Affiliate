<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AcademySeeder extends Seeder
{
    /**
     * Seed the Academy Platform data
     *
     * Usage: php artisan db:seed --class=AcademySeeder
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🎓 Seeding Academy Platform Data...');
        $this->command->info('');

        $this->call([
            LearningCategorySeeder::class,
            LearningArticleSeeder::class,
            QuizSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✅ Academy Platform seeded successfully!');
        $this->command->info('');
        $this->command->info('📚 Summary:');
        $this->command->info('   - 6 Learning Categories');
        $this->command->info('   - 10 Sample Courses');
        $this->command->info('   - 4 Quizzes with Questions');
        $this->command->info('');
        $this->command->info('🚀 You can now access:');
        $this->command->info('   - /admin/learning-center (Student View)');
        $this->command->info('   - /admin/instructor/dashboard (Instructor Dashboard)');
        $this->command->info('   - /admin/articles (Course Management)');
        $this->command->info('');
    }
}
