<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class Organization extends Model
{
    use Billable;

    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invitations()
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'organization_user');
    }

    public function addMember(User $user): void
    {
        $this->members()->attach($user->id);
    }

    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);

        if ($user->current_organization_id === $this->id) {
            $user->current_organization_id = null;
            $user->save();
        }
    }

    public function isMember(User $user): bool
    {
        return $this->members->contains($user);
    }

    public function inviteMember(string $email): OrganizationInvitation
    {
        return $this->invitations()->create([
            'email' => $email,
        ]);
    }

    protected function casts(): array
    {
        return [
            'personal' => 'boolean',
        ];
    }
}
