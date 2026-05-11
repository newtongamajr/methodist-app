<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\PersonNature;
use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Shared list/filter/scope logic for any component that lists Persons.
 *
 * Consumers declare their nature scope by overriding `getPersonNature()`:
 *  - return null for the all-persons listing (admin/people index)
 *  - return PersonNature::Pastor->value for a pastor-only listing, etc.
 *
 * Pattern mirrors template-new-galileo's ManagesPersons trait — the central
 * logic lives here, per-consumer specialization is just one method override.
 */
trait ManagesPersons
{
    use HasSortableColumns;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'nature')]
    public string $natureFilter = '';

    /**
     * Whether to include organizational Persons (the rows backing each
     * Region / District / Church) in the listing. Defaults to false so the
     * People index stays focused on humans; toggleable via UI.
     */
    #[Url(as: 'orgs')]
    public bool $includeOrganizations = false;

    /**
     * Override per-consumer to scope a listing to one nature.
     * Return null for the all-persons view.
     */
    protected function getPersonNature(): ?string
    {
        return null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingNatureFilter(): void
    {
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return ['name', 'birthdate'];
    }

    protected function defaultSortBy(): string
    {
        return 'name';
    }

    /**
     * Natures the filter dropdown should expose. Defaults to the individual
     * natures only on the all-persons view; org natures only show up when
     * the user opts in via `includeOrganizations`.
     */
    #[Computed]
    public function availableNatures(): array
    {
        if ($this->getPersonNature() !== null) {
            return [];
        }

        return $this->includeOrganizations
            ? PersonNature::options()
            : PersonNature::individualOptions();
    }

    #[Computed]
    public function persons(): LengthAwarePaginator
    {
        $q = Person::query()
            ->with('managingChurch:id,name')
            ->orderBy($this->sortBy, $this->sortDir);

        $lockedNature = $this->getPersonNature();
        if ($lockedNature !== null) {
            $q->whereJsonContains('natures', $lockedNature);
        } elseif ($this->natureFilter !== '') {
            $q->whereJsonContains('natures', $this->natureFilter);
        } elseif (! $this->includeOrganizations) {
            // Default: hide org-Persons (regions, districts, churches, HQ) from
            // the People listing. Toggle on to include them.
            $q->where(function ($qq) {
                foreach (PersonNature::organizational() as $org) {
                    $qq->whereJsonDoesntContain('natures', $org);
                }
            });
        }

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $q->where(fn ($qq) => $qq
                ->where('name', 'like', $term)
                ->orWhere('preferred_name', 'like', $term)
                ->orWhere('tax_id', 'like', $term));
        }

        return $q->paginate(20);
    }

    public function deletePerson(int $id): void
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);
        $person = Person::with('user')->findOrFail($id);
        // Refuse if a User account hangs off this Person — must delete the
        // User first (which cascades to Person via the FK on users.person_id).
        if ($person->user) {
            $this->addError('person', __('This person has a user account. Delete that account first.'));

            return;
        }
        $person->delete();
        unset($this->persons);
    }
}
