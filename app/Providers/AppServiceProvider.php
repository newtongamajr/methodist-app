<?php

namespace App\Providers;

use App\Models\Group;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Models\PersonRoleAssignment;
use App\Models\Pivots\ChurchUser;
use App\Models\Post;
use App\Models\PostComment;
use App\Observers\ChurchUserObserver;
use App\Observers\GroupObserver;
use App\Observers\PersonObserver;
use App\Observers\PersonRelationshipObserver;
use App\Observers\PersonRoleAssignmentObserver;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(PostComment::class, CommentPolicy::class);

        Person::observe(PersonObserver::class);
        PersonRelationship::observe(PersonRelationshipObserver::class);
        Group::observe(GroupObserver::class);
        PersonRoleAssignment::observe(PersonRoleAssignmentObserver::class);
        ChurchUser::observe(ChurchUserObserver::class);

        Blaze::optimize()->in(resource_path('views/components'));
    }
}
