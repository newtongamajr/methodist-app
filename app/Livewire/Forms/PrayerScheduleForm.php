<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\LocationMode;
use App\Models\PrayerSchedule;
use Livewire\Form;

class PrayerScheduleForm extends Form
{
    public ?PrayerSchedule $schedule = null;

    public ?int $church_id = null;

    public ?int $prayer_campaign_id = null;

    public string $date = '';

    /**
     * Create-mode multi-date selection. Each picked date materializes as its
     * own PrayerSchedule row sharing the rest of the configuration. Empty in
     * edit mode — that path goes through `$date` instead.
     *
     * @var array<int, string>
     */
    public array $dates = [];

    public string $start_time = '06:00';

    public string $end_time = '20:00';

    public int $slot_minutes = 60;

    public int $capacity_per_slot = 5;

    public string $mode = 'presential';

    public string $notes = '';

    public function rules(): array
    {
        // In edit mode only the single `date` matters; in create mode the
        // `dates` array drives the fan-out and `date` is left blank.
        $isEditing = (bool) $this->schedule;

        return [
            'church_id' => ['required', 'integer', 'exists:churches,id'],
            'prayer_campaign_id' => ['required', 'integer', 'exists:prayer_campaigns,id'],
            'date' => [$isEditing ? 'required' : 'nullable', 'date'],
            'dates' => [$isEditing ? 'nullable' : 'required', 'array', $isEditing ? 'nullable' : 'min:1'],
            'dates.*' => ['date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_minutes' => ['required', 'integer', 'in:30,60'],
            'capacity_per_slot' => ['required', 'integer', 'min:1', 'max:200'],
            'mode' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, LocationMode::cases()))],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function setSchedule(PrayerSchedule $schedule): void
    {
        $this->schedule = $schedule;
        $this->church_id = $schedule->church_id;
        $this->prayer_campaign_id = $schedule->prayer_campaign_id;
        $this->date = $schedule->date->format('Y-m-d');
        $this->start_time = substr($schedule->start_time, 0, 5);
        $this->end_time = substr($schedule->end_time, 0, 5);
        $this->slot_minutes = $schedule->slot_minutes;
        $this->capacity_per_slot = $schedule->capacity_per_slot;
        $this->mode = $schedule->mode->value;
        $this->notes = $schedule->notes ?? '';
    }
}
