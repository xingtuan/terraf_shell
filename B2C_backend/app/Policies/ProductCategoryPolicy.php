<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user, ProductCategory $productCategory): bool
    {
        return $productCategory->is_active || $user?->isAdmin() === true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ProductCategory $productCategory): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ProductCategory $productCategory): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
