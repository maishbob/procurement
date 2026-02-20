<?php

namespace App\Modules\Quality\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class CapaActionUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'capa_action_id',
        'user_id',
        'update_description',
        'progress_percentage',
        'attachments',
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'attachments' => 'array',
    ];

    /**
     * Relationships
     */
    public function capaAction(): BelongsTo
    {
        return $this->belongsTo(CapaAction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
