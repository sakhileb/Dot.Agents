<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'organization_id', 'organization_subscription_id',
        'invoice_number', 'status', 'subtotal', 'tax', 'total',
        'currency', 'due_at', 'paid_at', 'voided_at',
        'billing_address', 'line_items', 'payment_method',
        'external_invoice_id', 'pdf_url', 'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
        'billing_address' => 'array',
        'line_items' => 'array',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(OrganizationSubscription::class, 'organization_subscription_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'open' && $this->due_at?->isPast();
    }

    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
