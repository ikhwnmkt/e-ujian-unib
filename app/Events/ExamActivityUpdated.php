<?php

namespace App\Events;

use App\Models\Attempt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamActivityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Attempt $attempt;

    /**
     * Create a new event instance.
     */
    public function __construct(Attempt $attempt)
    {
        $this->attempt = $attempt;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('exam.' . $this->attempt->test_id);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'activity.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Load relasi jika belum di-load
        if (!$this->attempt->relationLoaded('user')) {
            $this->attempt->load('user:id,name,nomor_induk,avatar,prodi,kelas');
        }
        if (!$this->attempt->relationLoaded('answers')) {
            $this->attempt->load([
                'answers' => function ($q) {
                    $q->select('id', 'attempt_id', 'question_id', 'jawaban', 'jawaban_essai', 'updated_at')
                      ->orderBy('question_id');
                },
                'answers.question:id,pertanyaan,tipe',
            ]);
        }

        $test = $this->attempt->test;
        $allUserAttempts = Attempt::where('test_id', $this->attempt->test_id)
            ->where('user_id', $this->attempt->user_id)
            ->count();

        $lastUpdatedAnswer = $this->attempt->answers->sortByDesc('updated_at')->first();
        $lastActivity = $lastUpdatedAnswer?->updated_at ?? $this->attempt->updated_at;

        return [
            'attempt_id' => $this->attempt->id,
            'attempt_count' => $allUserAttempts,
            'user' => [
                'id' => $this->attempt->user->id,
                'name' => $this->attempt->user->name,
                'nomor_induk' => $this->attempt->user->nomor_induk,
                'avatar' => $this->attempt->user->avatar,
                'prodi' => $this->attempt->user->prodi,
                'kelas' => $this->attempt->user->kelas,
                'prodi_kelas' => $this->attempt->user->prodi_kelas,
            ],
            'started_at' => $this->attempt->started_at?->toIso8601String(),
            'finished_at' => $this->attempt->finished_at?->toIso8601String(),
            'is_finished' => !is_null($this->attempt->finished_at),
            'nilai' => $this->attempt->nilai !== null ? (float) $this->attempt->nilai : null,
            'updated_at' => $lastActivity?->toIso8601String(),
            'answers' => $this->attempt->answers->map(function ($ans) {
                return [
                    'question_id' => $ans->question_id,
                    'pertanyaan' => $ans->question?->pertanyaan,
                    'jawaban_essai' => $ans->jawaban_essai,
                    'updated_at' => $ans->updated_at?->toIso8601String(),
                ];
            })->values()->all(),
        ];
    }
}
