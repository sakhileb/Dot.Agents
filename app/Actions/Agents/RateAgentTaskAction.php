<?php

namespace App\Actions\Agents;

use App\Events\AgentTaskRated;
use App\Models\AgentTask;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * RateAgentTaskAction
 *
 * Records a 1–5 star satisfaction rating (and optional feedback) on a completed agent task.
 * Only the user who owns the task may rate it, and only once.
 */
class RateAgentTaskAction
{
    /**
     * Execute the rating action.
     *
     * @param  AgentTask  $task  The task to rate
     * @param  int  $rating  1–5 star satisfaction score
     * @param  string|null  $feedback  Optional freeform feedback
     * @return AgentTask Updated task
     */
    public function execute(AgentTask $task, int $rating, ?string $feedback = null): AgentTask
    {
        Gate::authorize('rate', $task);

        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages([
                'rating' => 'Rating must be between 1 and 5.',
            ]);
        }

        if ($task->rated_at !== null) {
            throw ValidationException::withMessages([
                'rating' => 'This task has already been rated.',
            ]);
        }

        $task->update([
            'user_rating' => $rating,
            'user_feedback' => $feedback,
            'rated_at' => now(),
        ]);

        event(new AgentTaskRated($task->fresh(), $rating, $feedback));

        return $task->fresh();
    }
}
