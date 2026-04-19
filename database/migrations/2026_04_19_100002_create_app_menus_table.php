<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dynamic menu items rendered in-app.
 *
 * `slot` identifies where the item appears:
 *   home_tab         — items in the bottom tab bar (rarely changed)
 *   home_quick_link  — the 4 tiles below the hero
 *   drawer           — hamburger menu
 *   profile_actions  — action list on /me
 *
 * `action` is a URL or deep-link understood by the Flutter router:
 *   tp://product/123
 *   tp://shop/456
 *   https://thaiprompt.app/promo/…
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_menus', function (Blueprint $t) {
            $t->id();
            $t->string('slot', 32)->index();
            $t->unsignedSmallInteger('order')->default(0);
            $t->string('icon', 64)->nullable();  // material icon name or url
            $t->string('label_th');
            $t->string('label_en')->nullable();
            $t->string('action');
            $t->boolean('enabled')->default(true);
            $t->string('min_app_version', 16)->nullable();
            $t->string('role')->nullable();
            $t->timestamp('visible_from')->nullable();
            $t->timestamp('visible_until')->nullable();
            $t->timestamps();

            $t->index(['slot', 'enabled', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_menus');
    }
};
