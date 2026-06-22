<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
{
    public function __construct(
        private readonly Order $order,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $statusDescricao = OrderStatusEnum::from($this->order->status_id)->description();

        return (new MailMessage())
            ->subject("Pedido {$this->order->ref} — status atualizado: {$statusDescricao}")
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line("O seu pedido de viagem **{$this->order->ref}** teve o status alterado para **{$statusDescricao}**.")
            ->line('Acesse o sistema para consultar os detalhes do seu pedido.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $statusDescricao = OrderStatusEnum::from($this->order->status_id)->description();

        return [
            'pedido_ref' => $this->order->ref,
            'status_id' => $this->order->status_id,
            'status_descricao' => $statusDescricao,
            'mensagem' => "O pedido {$this->order->ref} foi {$statusDescricao}.",
        ];
    }
}
