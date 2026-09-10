<?php
namespace App\Policies;
use App\Models\TimetableSlot;
use App\Models\User;
class TimetableSlotPolicy {
    public function before(User $user): ?bool { return $user->isSuperAdmin() ? true : null; }
    public function viewAny(User $user): bool { return $user->is_active && $user->school_id !== null; }
    public function view(User $user, TimetableSlot $slot): bool { return $user->school_id === $slot->school_id; }
    public function create(User $user): bool { return $user->isDirector() && $user->school_id !== null; }
    public function update(User $user, TimetableSlot $slot): bool { return $user->isDirector() && $user->school_id === $slot->school_id; }
    public function delete(User $user, TimetableSlot $slot): bool { return $this->update($user,$slot); }
}
