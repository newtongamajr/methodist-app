<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Adds the standard sortable-column wiring used by every admin index list:
 *
 * - $sortBy / $sortDir public properties bound to ?sort=&dir= query params
 * - sort($column) action that toggles direction on the same column or
 *   switches to a new column with a sensible default direction
 * - bootHasSortableColumns() seeds defaults on the first hit when the URL
 *   carries no sort/dir
 *
 * Components opt in via:
 *   use HasSortableColumns;
 *
 *   protected function sortableColumns(): array { return ['name', 'email']; }
 *   protected function defaultSortBy(): string  { return 'name'; }
 *
 * Components don't need to redeclare $sortBy / $sortDir; Livewire picks the
 * declarations up from this trait. To override the per-column default
 * direction, override defaultSortDirection().
 */
trait HasSortableColumns
{
    #[Url(as: 'sort')]
    public string $sortBy = '';

    #[Url(as: 'dir')]
    public string $sortDir = '';

    public function bootHasSortableColumns(): void
    {
        if ($this->sortBy === '' || ! in_array($this->sortBy, $this->sortableColumns(), true)) {
            $this->sortBy = $this->defaultSortBy();
        }
        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = $this->defaultSortDirection($this->sortBy);
        }
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortableColumns(), true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = $this->defaultSortDirection($column);
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * @return list<string>
     */
    abstract protected function sortableColumns(): array;

    abstract protected function defaultSortBy(): string;

    /**
     * Date-like and *_count columns default to desc on first click; everything
     * else defaults to asc. Override to customize per column.
     */
    protected function defaultSortDirection(string $column): string
    {
        $descByDefault = [
            'date',
            'updated_at',
            'created_at',
            'published_at',
            'start_date',
            'end_date',
            'starts_at',
        ];

        if (in_array($column, $descByDefault, true)) {
            return 'desc';
        }

        if (str_ends_with($column, '_count')) {
            return 'desc';
        }

        return 'asc';
    }
}
