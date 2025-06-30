<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'cnpj',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'logo_path',
        'settings',
        'plan',
        'max_users',
        'max_cases',
        'is_active',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });
    }

    // Relacionamentos
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    public function petitionTemplates(): HasMany
    {
        return $this->hasMany(PetitionTemplate::class);
    }

    public function workflowTemplates(): HasMany
    {
        return $this->hasMany(WorkflowTemplate::class);
    }

    // Relacionamentos com assinaturas
    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class)
                    ->whereIn('status', ['trial', 'active'])
                    ->latest();
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, CompanySubscription::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Métodos auxiliares
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isPast();
    }

    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->max_users;
    }

    public function canAddCase(): bool
    {
        return $this->cases()->count() < $this->max_cases;
    }

    public function getRemainingUsers(): int
    {
        return max(0, $this->max_users - $this->users()->count());
    }

    public function getRemainingCases(): int
    {
        return max(0, $this->max_cases - $this->cases()->count());
    }

    // Métodos relacionados às assinaturas
    public function hasActiveSubscription(): bool
    {
        return $this->currentSubscription && 
               $this->currentSubscription->isActive();
    }

    public function isOnTrial(): bool
    {
        return $this->currentSubscription && 
               $this->currentSubscription->isTrial();
    }

    public function getSubscriptionStatus(): string
    {
        if (!$this->currentSubscription) {
            return 'no_subscription';
        }

        return $this->currentSubscription->status;
    }

    public function getSubscriptionDaysRemaining(): int
    {
        if (!$this->currentSubscription) {
            return 0;
        }

        return $this->currentSubscription->daysUntilExpiration();
    }

    public function canAccessFeature(string $feature): bool
    {
        if (!$this->currentSubscription) {
            return false;
        }

        return $this->currentSubscription->subscriptionPlan->hasFeature($feature);
    }

    public function isWithinUserLimit(): bool
    {
        if (!$this->currentSubscription) {
            return false;
        }

        $plan = $this->currentSubscription->subscriptionPlan;
        
        if ($plan->isUnlimited('users')) {
            return true;
        }

        return $this->users()->count() <= $plan->max_users;
    }

    public function isWithinCaseLimit(): bool
    {
        if (!$this->currentSubscription) {
            return false;
        }

        $plan = $this->currentSubscription->subscriptionPlan;
        
        if ($plan->isUnlimited('cases')) {
            return true;
        }

        return $this->cases()->count() <= $plan->max_cases;
    }
}
