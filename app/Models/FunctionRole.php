<?php

namespace App\Models;

use Database\Factories\FunctionRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FunctionRole extends Model
{
    /** @use HasFactory<FunctionRoleFactory> */
    use HasFactory;

    protected $table = 'functions';

    protected $fillable = [
        'name',
        'slug',
        'applies_to',
        'max_holders',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'applies_to' => 'array',
            'is_active' => 'boolean',
            'max_holders' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PersonRoleAssignment::class, 'function_id');
    }

    public function appliesTo(string $value): bool
    {
        return in_array($value, $this->applies_to ?? [], true);
    }
}
