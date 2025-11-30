<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class OrganizationInvitationAcceptController extends Controller
{
    public function __invoke(OrganizationInvitation $invitation): RedirectResponse
    {
        $user = Auth::user();
        $organization = $invitation->organization;

        $organization->addMember($user);
        $invitation->delete();
        $user->switchOrganization($organization);

        return redirect()->route('dashboard');
    }
}
