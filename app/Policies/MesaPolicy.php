<?php

namespace App\Policies;

use App\Models\Ficha;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MesaPolicy
{
    /**
     * Determine if user is admin
     */
    private function isAdmin(User $user): bool
    {
        return $user->role_id == 1;
    }

    /**
     * Determine if user is the camarero assigned to the mesa
     */
    private function isCamarero(User $user, Ficha $mesa): bool
    {
        return $user->id === $mesa->camarero_id;
    }

    /**
     * Determine if user can manage mesas (not camarero role)
     */
    private function canManageMesas(User $user): bool
    {
        return $user->role_id < 4; // Roles < 4 pueden gestionar mesas
    }

    /**
     * Determine whether the user can view any mesas.
     */
    public function viewAny(User $user): bool
    {
        // Todos los camareros pueden ver todas las mesas
        return true;
    }

    /**
     * Determine whether the user can view the mesa.
     */
    public function view(User $user, Ficha $mesa): bool
    {
        // Todos pueden ver todas las mesas
        return true;
    }

    /**
     * Determine whether the user can create mesas.
     */
    public function create(User $user): bool
    {
        // Solo usuarios con permisos (no camareros)
        return $this->canManageMesas($user);
    }

    /**
     * Determine whether the user can update the mesa.
     */
    public function update(User $user, Ficha $mesa): bool
    {
        // Solo usuarios con permisos (no camareros)
        return $this->canManageMesas($user);
    }

    /**
     * Determine whether the user can delete the mesa.
     */
    public function delete(User $user, Ficha $mesa): bool
    {
        // Solo usuarios con permisos (no camareros)
        // Y la mesa debe estar libre
        return $this->canManageMesas($user) && $mesa->estado_mesa === 'libre';
    }

    /**
     * Determine whether the user can abrir a mesa.
     */
    public function abrir(User $user, Ficha $mesa): bool
    {
        // Cualquier camarero puede abrir una mesa libre
        return $mesa->estado_mesa === 'libre';
    }

    /**
     * Determine whether the user can tomar a mesa from another camarero.
     */
    public function tomar(User $user, Ficha $mesa): bool
    {
        // Mesa debe estar ocupada
        if ($mesa->estado_mesa !== 'ocupada') {
            return false;
        }

        // No puede tomar su propia mesa
        if ($this->isCamarero($user, $mesa)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can cerrar a mesa.
     */
    public function cerrar(User $user, Ficha $mesa): bool
    {
        // Mesa debe estar ocupada
        if ($mesa->estado_mesa !== 'ocupada') {
            return false;
        }

        // Debe ser el camarero asignado o admin
        return $this->isCamarero($user, $mesa) || $this->isAdmin($user);
    }

    /**
     * Determine whether the user can liberar a mesa cerrada.
     */
    public function liberar(User $user, Ficha $mesa): bool
    {
        // Mesa debe estar cerrada
        if ($mesa->estado_mesa !== 'cerrada') {
            return false;
        }

        // Debe ser el camarero asignado o admin
        return $this->isCamarero($user, $mesa) || $this->isAdmin($user);
    }

    /**
     * Determine whether the user can add products to the mesa.
     */
    public function addProducts(User $user, Ficha $mesa): bool
    {
        // Mesa debe estar ocupada
        if ($mesa->estado_mesa !== 'ocupada') {
            return false;
        }

        // Debe ser el camarero asignado o admin
        return $this->isCamarero($user, $mesa) || $this->isAdmin($user);
    }

    /**
     * Determine whether the user can reordenar mesas.
     */
    public function reordenar(User $user): bool
    {
        // Solo usuarios con permisos (no camareros)
        return $this->canManageMesas($user);
    }
}
