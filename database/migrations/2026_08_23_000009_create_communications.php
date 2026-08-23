<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->string('priority', 16)->default('normal'); // normal | high
            $table->boolean('pinned')->default(false);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Audience targeting is enforced server-side at read time, never by UI filtering.
        Schema::create('announcement_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 24); // university | role | faculty | department | programme | course_offering
            $table->string('scope_id')->nullable(); // entity id, or a role key such as "staff"
            $table->index(['scope', 'scope_id']);
        });

        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 320);
            $table->longText('body');
            $table->string('category', 24)->default('news'); // news | research | sports | community
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('campus_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('location', 191)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('category', 24)->default('general'); // orientation | public_lecture | career_fair | academic | general
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 24)->nullable();
            $table->string('subject', 191);
            $table->text('message');
            $table->string('status', 16)->default('new'); // new | in_progress | resolved
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
        Schema::dropIfExists('campus_events');
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('announcement_audiences');
        Schema::dropIfExists('announcements');
    }
};
