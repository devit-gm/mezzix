<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductoPolicy
{
    /**
     * Determine if user is admin or manager
     */
    private function canManageProducts(User $user): bool
    {
        // Solo roles < 4 pueden gestionar productos (no camareros)
        return $user->role_id < 4;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Todos pueden ver productos
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Producto $producto): bool
    {
        // Todos pueden ver productos
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManageProducts($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Producto $producto): bool
    {
        return $this->canManageProducts($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Producto $producto): bool
    {
        return $this->canManageProducts($user);
    }

    /**
     * Determine whether the user can manage inventory.
     */
    public function manageInventory(User $user): bool
    {
        return $this->canManageProducts($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Producto $producto): bool
    {
        return $user->role_id == 1; // Solo admin
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Producto $producto): bool
    {
        return $user->role_id == 1; // Solo admin
    }
}
