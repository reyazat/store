<?php

namespace App\Policies;

use App\Models\Product;
use IamApi\Models\User;

class ProductPolicy
{
    /**
     * Determine if the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        // Super admin can view all products
        if ($user->is_super_admin) {
            return true;
        }

        // Store members can view their own products
        return $user->organization_id === $product->organization_id;
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        // Super admin cannot create products (no organization context)
        if ($user->is_super_admin) {
            return false;
        }

        // Only store members can create products
        return $user->organization_id !== null;
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        // Super admin cannot update products
        if ($user->is_super_admin) {
            return false;
        }

        // User must belong to the same organization as the product
        return $user->organization_id === $product->organization_id;
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        // Super admin cannot delete products
        if ($user->is_super_admin) {
            return false;
        }

        // User must belong to the same organization as the product
        return $user->organization_id === $product->organization_id;
    }

    /**
     * Determine if the user can restore the product.
     */
    public function restore(User $user, Product $product): bool
    {
        return $this->delete($user, $product);
    }

    /**
     * Determine if the user can permanently delete the product.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $this->delete($user, $product);
    }
}
