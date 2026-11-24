<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/** The current state of the presentation, delivered to the remote control. */
class RemoteState implements ShouldBroadcastNow
{
    use Dispatchable;

    /** @param array<string, mixed> $state */
    public function __construct(public string $token, public array $state) {}

    public function broadcastOn(): Channel
    {
        return new Channel("remote.{$this->token}.state");
    }

    public function broadcastAs(): string
    {
        return 'remote.state';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->state;
    }
}
