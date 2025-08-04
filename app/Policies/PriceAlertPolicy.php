<?php
namespace App\Policies;

use App\Models\PriceAlert;
use App\Models\User;

class PriceAlertPolicy
{
    public function update(User $user, PriceAlert $alert)
    {
        return $user->id === $alert->user_id;
    }

    public function delete(User $user, PriceAlert $alert)
    {
        return $user->id === $alert->user_id;
    }
}