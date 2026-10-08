<?php

namespace App\Policies;

use App\Models\ProcurementRequest;
use App\Models\User;

class ProcurementRequestPolicy
{
    public function view(User $user, ProcurementRequest $procurementRequest): bool
    {
        return ProcurementRequest::visibleTo($user)->whereKey($procurementRequest->id)->exists();
    }

    public function update(User $user, ProcurementRequest $procurementRequest): bool
    {
        return $procurementRequest->requester_id === $user->id && $procurementRequest->status === 'draft';
    }

    public function reviewFinance(User $user, ProcurementRequest $procurementRequest): bool
    {
        return $procurementRequest->canBeReviewedBy($user, 'finance');
    }

    public function reviewDirector(User $user, ProcurementRequest $procurementRequest): bool
    {
        return $procurementRequest->canBeReviewedBy($user, 'director');
    }
}
