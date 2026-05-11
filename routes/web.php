<?php

use App\Http\Middleware\EnsureCanManageChurches;
use App\Http\Middleware\EnsureCanManageFasting;
use App\Http\Middleware\EnsureCanManagePosts;
use App\Http\Middleware\EnsureCanManagePrayer;
use App\Http\Middleware\EnsureCanManageUsers;
use App\Http\Middleware\EnsureCanModerateComments;
use Illuminate\Support\Facades\Route;

// Marketing landing is the home page; the public posts feed lives at /posts.
Route::livewire('/', 'landing')->name('home');
Route::livewire('posts', 'posts.index')->middleware('throttle:60,1')->name('posts.index');
Route::livewire('posts/{slug}', 'posts.show')->middleware('throttle:60,1')->name('posts.show');

Route::livewire('profile', 'profile.show')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::livewire('prayer', 'prayer.index')->name('prayer.index');
    Route::livewire('fasting', 'fasting.index')->name('fasting.index');
});

Route::middleware(['auth', EnsureCanManagePosts::class])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('posts', 'admin.posts.index')->name('posts.index');
    Route::livewire('posts/create', 'admin.posts.editor')->name('posts.create');
    Route::livewire('posts/{postId}/edit', 'admin.posts.editor')->name('posts.edit');
});

Route::middleware(['auth', EnsureCanModerateComments::class])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('comments', 'admin.comments.index')->name('comments.index');
});

Route::middleware(['auth', EnsureCanManagePrayer::class])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('prayer-schedules', 'admin.prayer-schedules.index')->name('prayer-schedules.index');
    Route::livewire('prayer-schedules/create', 'admin.prayer-schedules.editor')->name('prayer-schedules.create');
    Route::livewire('prayer-schedules/{scheduleId}/edit', 'admin.prayer-schedules.editor')->name('prayer-schedules.edit');

    Route::livewire('prayer-campaigns', 'admin.prayer-campaigns.index')->name('prayer-campaigns.index');
    Route::livewire('prayer-campaigns/create', 'admin.prayer-campaigns.editor')->name('prayer-campaigns.create');
    Route::livewire('prayer-campaigns/{campaignId}/edit', 'admin.prayer-campaigns.editor')->name('prayer-campaigns.edit');
});

Route::middleware(['auth', EnsureCanManageChurches::class])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('churches', 'admin.churches.index')->name('churches.index');
    Route::livewire('churches/create', 'admin.churches.editor')->name('churches.create');
    Route::livewire('churches/{churchId}/edit', 'admin.churches.editor')->name('churches.edit');

    Route::livewire('churches/{churchId}/pastors', 'admin.churches.pastors.index')->name('churches.pastors.index');
    Route::livewire('churches/{churchId}/pastors/create', 'admin.churches.pastors.editor')->name('churches.pastors.create');
    Route::livewire('churches/{churchId}/pastors/{assignmentId}/edit', 'admin.churches.pastors.editor')->name('churches.pastors.edit');

    Route::livewire('regions', 'admin.regions.index')->name('regions.index');
    Route::livewire('regions/create', 'admin.regions.editor')->name('regions.create');
    Route::livewire('regions/{regionId}/edit', 'admin.regions.editor')->name('regions.edit');

    Route::livewire('districts', 'admin.districts.index')->name('districts.index');
    Route::livewire('districts/create', 'admin.districts.editor')->name('districts.create');
    Route::livewire('districts/{districtId}/edit', 'admin.districts.editor')->name('districts.edit');

    Route::livewire('groups', 'admin.groups.index')->name('groups.index');
    Route::livewire('groups/create', 'admin.groups.editor')->name('groups.create');
    Route::livewire('groups/{groupId}/edit', 'admin.groups.editor')->name('groups.edit');
});

Route::middleware(['auth', EnsureCanManageUsers::class])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('users', 'admin.users.index')->name('users.index');
    Route::livewire('users/create', 'admin.users.editor')->name('users.create');
    Route::livewire('users/{userId}/edit', 'admin.users.editor')->name('users.edit');

    Route::livewire('members', 'admin.members.index')->name('members.index');
    Route::livewire('members/create', 'admin.members.editor')->name('members.create');
    Route::livewire('members/{userId}/edit', 'admin.members.editor')->name('members.edit');

    Route::livewire('people', 'admin.people.index')->name('people.index');
    Route::livewire('people/create', 'admin.people.editor')->name('people.create');
    Route::livewire('people/{personId}/edit', 'admin.people.editor')->name('people.edit');
});

Route::middleware(['auth', EnsureCanManageFasting::class])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('fasting-campaigns', 'admin.fasting-campaigns.index')->name('fasting-campaigns.index');
    Route::livewire('fasting-campaigns/create', 'admin.fasting-campaigns.editor')->name('fasting-campaigns.create');
    Route::livewire('fasting-campaigns/{campaignId}/edit', 'admin.fasting-campaigns.editor')->name('fasting-campaigns.edit');
});

require __DIR__.'/auth.php';
