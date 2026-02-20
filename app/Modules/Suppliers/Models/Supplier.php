<?php

namespace App\Modules\Suppliers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_code',
        'business_name',
        'trading_name',
        'registration_number',
        'kra_pin',
        'vat_number',
        'physical_address',
        'postal_address',
        'city',
        'country',
        'phone',
        'email',
        'website',
        'contact_person_name',
        'contact_person_phone',
        'contact_person_email',
        'bank_name',
        'bank_branch',
        'bank_account_number',
        'bank_account_name',
        'swift_code',
        'is_tax_compliant',
        'tax_compliance_cert_number',
        'tax_compliance_cert_expiry',
        'payment_terms',
        'payment_method',
        'subject_to_wht',
        'wht_type',
        'wht_rate',
        'performance_rating',
        'total_orders',
        'total_orders_value',
        'on_time_delivery_percentage',
        'quality_rating',
        'is_active',
        'is_blacklisted',
        'blacklist_reason',
        'blacklisted_at',
        'blacklisted_by',
        'notes',
        // ASL fields
        'asl_status',
        'asl_approved_at',
        'asl_approved_by',
        'asl_review_due_at',
        'asl_categories',
        'onboarding_status',
    ];

    protected $casts = [
        'tax_compliance_cert_expiry' => 'date',
        'is_tax_compliant' => 'boolean',
        'subject_to_wht' => 'boolean',
        'wht_rate' => 'decimal:2',
        'performance_rating' => 'decimal:2',
        'total_orders_value' => 'decimal:2',
        'on_time_delivery_percentage' => 'decimal:2',
        'quality_rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_blacklisted' => 'boolean',
        'blacklisted_at' => 'datetime',
        'deleted_at' => 'datetime',
        // ASL
        'asl_approved_at' => 'datetime',
        'asl_review_due_at' => 'date',
        'asl_categories' => 'array',
    ];

    /**
     * Relationships
     */
    public function categories(): HasMany
    {
        return $this->hasMany(SupplierCategory::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(SupplierPerformanceReview::class);
    }

    public function blacklistHistory(): HasMany
    {
        return $this->hasMany(SupplierBlacklistHistory::class);
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('is_blacklisted', false)
            ->whereNull('deleted_at');
    }

    public function scopeTaxCompliant($query)
    {
        return $query->where('is_tax_compliant', true)
            ->where(function ($q) {
                $q->whereNull('tax_compliance_cert_expiry')
                    ->orWhere('tax_compliance_cert_expiry', '>=', Carbon::today());
            });
    }

    public function scopeBlacklisted($query)
    {
        return $query->where('is_blacklisted', true);
    }

    public function scopeInCategory($query, int $categoryId)
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('supplier_categories.id', $categoryId);
        });
    }

    public function scopeWithPerformanceRating($query, float $minRating)
    {
        return $query->where('performance_rating', '>=', $minRating);
    }

    public function scopeHighPerformers($query)
    {
        return $query->where('performance_rating', '>=', 4.0)
            ->where('on_time_delivery_percentage', '>=', 90);
    }

    /**
     * Helper Methods
     */
    public function isTaxComplianceCertExpiring(int $daysThreshold = 30): bool
    {
        if (!$this->tax_compliance_cert_expiry) {
            return false;
        }

        return $this->tax_compliance_cert_expiry->lte(Carbon::today()->addDays($daysThreshold));
    }

    public function isTaxComplianceCertExpired(): bool
    {
        if (!$this->tax_compliance_cert_expiry) {
            return false;
        }

        return $this->tax_compliance_cert_expiry->lt(Carbon::today());
    }

    public function isEligibleForBusiness(): bool
    {
        return $this->is_active
            && !$this->is_blacklisted
            && $this->is_tax_compliant
            && !$this->isTaxComplianceCertExpired();
    }

    public function hasValidKRAPin(): bool
    {
        if (!$this->kra_pin) {
            return false;
        }

        // KRA PIN format: A001234567Z
        return preg_match('/^[A-Z]\d{9}[A-Z]$/', $this->kra_pin) === 1;
    }

    public function updatePerformanceMetrics(array $metrics): void
    {
        $this->update([
            'performance_rating' => $metrics['performance_rating'] ?? $this->performance_rating,
            'on_time_delivery_percentage' => $metrics['on_time_delivery_percentage'] ?? $this->on_time_delivery_percentage,
            'quality_rating' => $metrics['quality_rating'] ?? $this->quality_rating,
        ]);
    }

    public function blacklist(string $reason, int $blacklistedBy): void
    {
        $this->update([
            'is_blacklisted' => true,
            'blacklist_reason' => $reason,
            'blacklisted_at' => Carbon::now(),
            'blacklisted_by' => $blacklistedBy,
        ]);

        // Record in history
        $this->blacklistHistory()->create([
            'action' => 'blacklisted',
            'reason' => $reason,
            'actioned_by' => $blacklistedBy,
            'actioned_at' => Carbon::now(),
        ]);
    }

    public function unblacklist(string $reason, int $actionedBy): void
    {
        $this->update([
            'is_blacklisted' => false,
            'blacklist_reason' => null,
        ]);

        // Record in history
        $this->blacklistHistory()->create([
            'action' => 'unblacklisted',
            'reason' => $reason,
            'actioned_by' => $actionedBy,
            'actioned_at' => Carbon::now(),
        ]);
    }

    /**
     * Formatted Attributes
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->trading_name ?: $this->business_name;
    }

    public function getFormattedKRAPinAttribute(): string
    {
        if (!$this->kra_pin) {
            return 'N/A';
        }
        return strtoupper($this->kra_pin);
    }

    public function getComplianceStatusAttribute(): string
    {
        if ($this->is_blacklisted) {
            return 'Blacklisted';
        }

        if (!$this->is_tax_compliant) {
            return 'Non-Compliant';
        }

        if ($this->isTaxComplianceCertExpired()) {
            return 'Expired Certificate';
        }

        if ($this->isTaxComplianceCertExpiring()) {
            return 'Certificate Expiring';
        }

        return 'Compliant';
    }

    public function getComplianceColorAttribute(): string
    {
        return match ($this->compliance_status) {
            'Blacklisted' => 'red',
            'Non-Compliant' => 'red',
            'Expired Certificate' => 'orange',
            'Certificate Expiring' => 'yellow',
            'Compliant' => 'green',
            default => 'gray',
        };
    }

    public function getPerformanceRatingLabelAttribute(): string
    {
        $rating = $this->performance_rating;

        if ($rating >= 4.5) {
            return 'Excellent';
        } elseif ($rating >= 4.0) {
            return 'Very Good';
        } elseif ($rating >= 3.0) {
            return 'Good';
        } elseif ($rating >= 2.0) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }

    public function getPerformanceColorAttribute(): string
    {
        $rating = $this->performance_rating;

        if ($rating >= 4.0) {
            return 'green';
        } elseif ($rating >= 3.0) {
            return 'blue';
        } elseif ($rating >= 2.0) {
            return 'yellow';
        } else {
            return 'red';
        }
    }
}
