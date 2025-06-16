<?php

namespace App\Policies;

use App\Models\Content;
use App\Models\User;

class ContentPolicy
{
    /**
     * Determine whether the user can view any content.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Anyone can view content
    }

    /**
     * Determine whether the user can view the content.
     */
    public function view(?User $user, Content $content): bool
    {
        return true; // Anyone can view content
    }

    /**
     * Determine whether the user can create content.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create content') || $user->hasRole(['admin', 'author']);
    }

    /**
     * Determine whether the user can update the content.
     */
    public function update(User $user, Content $content): bool
    {
        return $user->hasPermissionTo('update content') || 
               $user->hasRole('admin') || 
               ($user->hasRole('author') && $content->user_id === $user->id);
    }

    /**
     * Determine whether the user can delete the content.
     */
    public function delete(User $user, Content $content): bool
    {
        return $user->hasPermissionTo('delete content') || 
               $user->hasRole('admin') || 
               ($user->hasRole('author') && $content->user_id === $user->id);
    }

    /**
     * Determine whether the user can publish content.
     */
    public function publish(User $user, Content $content): bool
    {
        return $user->hasPermissionTo('publish content') || 
               $user->hasRole('admin') || 
               ($user->hasRole('author') && $content->user_id === $user->id);
    }

    /**
     * Determine whether the user can draft content.
     */
    public function draft(User $user, Content $content): bool
    {
        return $user->hasPermissionTo('draft content') || 
               $user->hasRole('admin') || 
               ($user->hasRole('author') && $content->user_id === $user->id);
    }

    /**
     * Determine whether the user can archive content.
     */
    public function archive(User $user): bool
    {
        return $user->hasPermissionTo('archive content') || $user->hasRole('admin');
    }
}
