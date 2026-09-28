<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara cada vez que un pedido nace o cambia de estado.
 * Si más adelante conectas Laravel Reverb / Pusher, los celulares
 * lo reciben al instante. Mientras tanto, la app del mesero también
 * consulta /mesero/live cada pocos segundos.
 */
class PedidoCambio implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Pedido $pedido, public string $accion = 'actualizado')
    {
        $this->pedido->loadMissing('mesa', 'items');
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('restaurante.'.$this->pedido->restaurante_id.'.cocina'),
        ];

        $meseroId = $this->pedido->mesa?->mesero_id;
        if ($meseroId) {
            $channels[] = new PrivateChannel('mesero.'.$meseroId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'pedido.cambio';
    }

    public function broadcastWith(): array
    {
        return [
            'accion' => $this->accion,
            'pedido' => [
                'id'     => $this->pedido->id,
                'codigo' => $this->pedido->codigo,
                'estado' => $this->pedido->estado,
                'tipo'   => $this->pedido->tipo,
                'mesa'   => $this->pedido->mesa?->numero,
                'mesa_id'=> $this->pedido->mesa_id,
            ],
        ];
    }
}