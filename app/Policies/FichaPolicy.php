<?php

namespace App\Policies;

use App\Models\Ficha;
use App\Models\User;
use App\Models\FichaUsuario;
use Illuminate\Auth\Access\Response;

class FichaPolicy
{
    /**
     * Determine if user is admin
     */
    private function isAdmin(User $user): bool
    {
        return $user->role_id == 1;
    }

    /**
     * Determine if user is owner of ficha
     */
    private function isOwner(User $user, Ficha $ficha): bool
    {
        return $user->id === $ficha->user_id;
    }

    /**
     * Determine if user is inscrito in ficha
     */
    private function isInscrito(User $user, Ficha $ficha): bool
    {
        return $ficha->inscritos->contains('user_id', $user->id);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden ver la lista de fichas
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ficha $ficha): bool
    {
        // Eventos públicos (tipo 4): todos pueden ver
        if ($ficha->tipo == 4) {
            return true;
        }

        // Otras fichas: solo admin, propietario o inscritos
        return $this->isAdmin($user) 
            || $this->isOwner($user, $ficha) 
            || $this->isInscrito($user, $ficha);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Todos los usuarios pueden crear fichas
        // (En modo agencia_eventos, solo admins pueden crear eventos - revisar en controlador)
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ficha $ficha): bool
    {
        // Solo admin o propietario pueden editar
        return $this->isAdmin($user) || $this->isOwner($user, $ficha);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ficha $ficha): bool
    {
        // Solo se pueden borrar fichas abiertas (estado 0)
        if ($ficha->estado != 0) {
            return false;
        }

        // Admin, propietario o inscrito pueden borrar
        return $this->isAdmin($user) 
            || $this->isOwner($user, $ficha) 
            || $this->isInscrito($user, $ficha);
    }

    /**
     * Determine whether the user can add products to the ficha.
     */
    public function addProducts(User $user, Ficha $ficha): bool
    {
        // Solo admin, propietario o inscrito pueden añadir productos
        return $this->isAdmin($user) 
            || $this->isOwner($user, $ficha) 
            || $this->isInscrito($user, $ficha);
    }

    /**
     * Determine whether the user can manage users/invitados in the ficha.
     */
    public function manageUsers(User $user, Ficha $ficha): bool
    {
        // Solo admin, propietario o inscrito pueden gestionar usuarios
        return $this->isAdmin($user) 
            || $this->isOwner($user, $ficha) 
            || $this->isInscrito($user, $ficha);
    }

    /**
     * Determine whether the user can manage gastos in the ficha.
     */
    public function manageGastos(User $user, Ficha $ficha): bool
    {
        // Solo admin o propietario pueden gestionar gastos
        return $this->isAdmin($user) || $this->isOwner($user, $ficha);
    }

    /**
     * Determine whether the user can inscribirse to an event (tipo 4).
     */
    public function inscribirse(User $user, Ficha $ficha): bool
    {
        // Solo eventos públicos (tipo 4)
        if ($ficha->tipo != 4) {
            return false;
        }

        // No estar ya inscrito
        if ($this->isInscrito($user, $ficha)) {
            return false;
        }

        // Verificar que haya plazas disponibles
        if ($ficha->aforo_maximo && $ficha->inscritos_actuales >= $ficha->aforo_maximo) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can cancel inscription to an event.
     */
    public function cancelarInscripcion(User $user, Ficha $ficha): bool
    {
        // Solo eventos públicos (tipo 4)
        if ($ficha->tipo != 4) {
            return false;
        }

        // Debe estar inscrito
        return $this->isInscrito($user, $ficha);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ficha $ficha): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ficha $ficha): bool
    {
        return $this->isAdmin($user);
    }
}
