<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LearningCenterController extends Controller
{
    /**
     * Display the learning center index page
     */
    public function index()
    {
        $categories = [
            [
                'id' => 'ai-installation',
                'name' => 'การติดตั้ง AI',
                'description' => 'เรียนรู้วิธีการติดตั้งและตั้งค่า AI โมเดลต่างๆ',
                'icon' => '🤖',
                'color' => 'from-purple-600 to-indigo-600',
                'articles_count' => 8
            ],
            [
                'id' => 'ai-models',
                'name' => 'โมเดล AI',
                'description' => 'ทำความรู้จักกับโมเดล AI แต่ละตัว และวิธีการใช้งาน',
                'icon' => '🧠',
                'color' => 'from-blue-600 to-cyan-600',
                'articles_count' => 12
            ],
            [
                'id' => 'troubleshooting',
                'name' => 'การแก้ปัญหา',
                'description' => 'แก้ไขปัญหาที่พบบ่อยในการใช้งาน AI',
                'icon' => '🔧',
                'color' => 'from-red-600 to-pink-600',
                'articles_count' => 6
            ],
            [
                'id' => 'best-practices',
                'name' => 'Best Practices',
                'description' => 'แนวทางปฏิบัติที่ดีที่สุดในการใช้งาน AI',
                'icon' => '⭐',
                'color' => 'from-amber-600 to-orange-600',
                'articles_count' => 10
            ],
            [
                'id' => 'api-integration',
                'name' => 'การเชื่อมต่อ API',
                'description' => 'วิธีการเชื่อมต่อและใช้งาน AI API',
                'icon' => '🔌',
                'color' => 'from-green-600 to-emerald-600',
                'articles_count' => 7
            ],
            [
                'id' => 'performance',
                'name' => 'ปรับแต่งประสิทธิภาพ',
                'description' => 'เพิ่มประสิทธิภาพและความเร็วของ AI',
                'icon' => '⚡',
                'color' => 'from-yellow-600 to-amber-600',
                'articles_count' => 5
            ],
        ];

        $popular_articles = [
            [
                'id' => 1,
                'title' => 'เริ่มต้นติดตั้ง DeepSeek R1 บนเซิร์ฟเวอร์ของคุณ',
                'category' => 'การติดตั้ง AI',
                'views' => 2500,
                'duration' => '10 นาที'
            ],
            [
                'id' => 2,
                'title' => 'เปรียบเทียบ Llama 3.3 vs DeepSeek R1',
                'category' => 'โมเดล AI',
                'views' => 1800,
                'duration' => '8 นาที'
            ],
            [
                'id' => 3,
                'title' => 'แก้ปัญหา Out of Memory เมื่อรันโมเดล AI',
                'category' => 'การแก้ปัญหา',
                'views' => 1500,
                'duration' => '5 นาที'
            ],
            [
                'id' => 4,
                'title' => 'เลือก Quantization ที่เหมาะสมกับเซิร์ฟเวอร์',
                'category' => 'Best Practices',
                'views' => 1200,
                'duration' => '7 นาที'
            ],
        ];

        return view('admin.learning-center.index', compact('categories', 'popular_articles'));
    }

    /**
     * Display articles in a specific category
     */
    public function category($category)
    {
        // TODO: Implement category view
        return redirect()->route('admin.learning-center.index');
    }

    /**
     * Display a specific article
     */
    public function article($id)
    {
        // TODO: Implement article view
        return redirect()->route('admin.learning-center.index');
    }
}
