<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasOrganizationScope;

    protected $fillable = [
        'webhook_id', 'organization_id', 'event', 'payload',
        'status', 'http_status', 'response_body', 'attempt',
        'duration_ms', 'error_message', 'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
