<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class CompanySubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'ends_at',
        'amount',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'ends_at' => 'datetime',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relacionamentos
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function successfulPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', 'paid');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('current_period_end', '<=', now()->addDays($days))
                    ->whereIn('status', ['active', 'trial']);
    }

    // Métodos auxiliares
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->current_period_end && $this->current_period_end->isPast());
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->current_period_end && 
               $this->current_period_end->lte(now()->addDays($days)) &&
               in_array($this->status, ['active', 'trial']);
    }

    public function daysUntilExpiration(): int
    {
        if (!$this->current_period_end) {
            return 0;
        }

        return max(0, now()->diffInDays($this->current_period_end, false));
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'ends_at' => $this->current_period_end,
        ]);
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'cancelled_at' => null,
            'ends_at' => null,
        ]);
    }

    public function renew(): void
    {
        $plan = $this->subscriptionPlan;
        $newPeriodStart = $this->current_period_end ?? now();
        
        $newPeriodEnd = match($plan->billing_cycle) {
            'monthly' => $newPeriodStart->addMonth(),
            'quarterly' => $newPeriodStart->addMonths(3),
            'annual' => $newPeriodStart->addYear(),
            default => $newPeriodStart->addMonth(),
        };

        $this->update([
            'status' => 'active',
            'current_period_start' => $newPeriodStart,
            'current_period_end' => $newPeriodEnd,
            'cancelled_at' => null,
            'ends_at' => null,
        ]);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->amount, 2, ',', '.');
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'trial' => 'Trial',
            'active' => 'Ativo',
            'suspended' => 'Suspenso',
            'cancelled' => 'Cancelado',
            'expired' => 'Expirado',
            default => ucfirst($this->status),
        };
    }
}
