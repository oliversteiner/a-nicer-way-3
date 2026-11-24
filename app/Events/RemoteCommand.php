<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/** A command from the remote control, delivered to the presentation. */
class RemoteCommand implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public string $token,
        public string $action,
        public ?int $value = null,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("remote.{$this->token}.commands");
    }

    public function broadcastAs(): string
    {
        return 'remote.command';
    }

    /** @return array{action: string, value: int|null} */
    public function broadcastWith(): array
    {
        return ['action' => $this->action, 'value' => $this->value];
    }
}
