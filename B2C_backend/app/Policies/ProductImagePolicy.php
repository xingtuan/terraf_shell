<?php

namespace App\Policies;

use App\Models\ProductImage;
use App\Models\User;

class ProductImagePolicy
{
    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user, ProductImage $productImage): bool
    {
        return $productImage->product?->isPublished() ?? false
            || $user?->isAdmin() === true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ProductImage $productImage): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ProductImage $productImage): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
