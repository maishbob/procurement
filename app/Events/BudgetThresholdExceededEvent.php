<?php

namespace App\Events;

use App\Models\BudgetLine;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BudgetThresholdExceededEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public BudgetLine $budgetLine,
        public float $percentageUsed,
        public string $threshold = '80%'
    ) {
        $this->broadcastOn(new PrivateChannel('budgets.' . $budgetLine->id));
    }

    public function broadcastAs(): string
    {
        return 'budget.threshold-exceeded';
    }

    public function broadcastWith(): array
    {
        return [
            'budget_id' => $this->budgetLine->id,
            'budget_description' => $this->budgetLine->description,
            'department_id' => $this->budgetLine->department_id,
            'department_name' => $this->budgetLine->department?->name,
            'amount_allocated' => $this->budgetLine->amount_allocated,
            'amount_committed' => $this->budgetLine->amount_committed,
            'amount_executed' => $this->budgetLine->amount_executed,
            'percentage_used' => round($this->percentageUsed, 2) . '%',
            'threshold' => $this->threshold,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
