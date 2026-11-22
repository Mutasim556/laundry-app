<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
    public function view(User $user, Booking $booking): Response
    {
        if ((int) $booking->user_id === (int) $user->id) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to view this booking.');
    }

    public function update(User $user, Booking $booking): Response
    {
        if ($booking->isConfirmedAndLocked()) {
            return Response::deny('Confirmed bookings are permanently locked and cannot be modified.');
        }

        return (int) $booking->user_id === (int) $user->id
            ? Response::allow()
            : Response::deny('You do not have permission to update this booking.');
    }

    public function delete(User $user, Booking $booking): Response
    {
        return Response::deny('Confirmed bookings cannot be deleted by customers.');
    }
}
