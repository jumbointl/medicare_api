<?php

namespace App\Events;

use App\Models\AppointmentModel;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DoctorJoinedVideoAppointment implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $appointment_id;
    public ?string $meeting_id;
    public ?string $meeting_link;
    public ?string $video_provider;
    public ?string $doctor_joined_at;

    public function __construct(AppointmentModel $appointment)
    {
        $this->appointment_id = (int) $appointment->id;
        $this->meeting_id = $appointment->meeting_id ? (string) $appointment->meeting_id : null;
        $this->meeting_link = $appointment->meeting_link ? (string) $appointment->meeting_link : null;
        $this->video_provider = $appointment->video_provider ? (string) $appointment->video_provider : null;
        $this->doctor_joined_at = $this->normalizeDateTime($appointment->doctor_joined_at ?? null);
    }

    /**
     * Keep this public channel for compatibility with current app code:
     * appointment-video.{id}
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('appointment-video.' . $this->appointment_id),
        ];
    }

    /**
     * Keep this event name for compatibility with current app code:
     * doctor.joined
     */
    public function broadcastAs(): string
    {
        return 'doctor.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->appointment_id,
            'meeting_id' => $this->meeting_id,
            'meeting_link' => $this->meeting_link,
            'video_provider' => $this->video_provider,
            'doctor_joined_at' => $this->doctor_joined_at,
        ];
    }

    private function normalizeDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return Carbon::parse($value)->toDateTimeString();
    }
}