<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('restaurante.{restauranteId}.cocina', function ($user, $restauranteId) {
    return (int) $user->restaurante_id === (int) $restauranteId
        && in_array($user->role, ['admin', 'cajero', 'cocina', 'mesero'], true);
});

Broadcast::channel('mesero.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
