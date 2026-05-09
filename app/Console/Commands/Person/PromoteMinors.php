<?php

namespace App\Console\Commands\Person;

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Person;
use Illuminate\Console\Command;

class PromoteMinors extends Command
{
    protected $signature = 'person:promote-minors {--dry-run : List the changes without applying them}';

    protected $description = 'Promote children → teenagers and teenagers → adults based on birthdate (run nightly)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $promotedToTeenager = $this->promote(
            from: PersonNature::Child,
            to: PersonNature::Teenager,
            ageMin: Person::CHILD_MAX_AGE + 1,
            ageMax: Person::TEENAGER_MAX_AGE,
            dry: $dry,
        );

        // Teenager → adult: drop the Teenager nature; we don't auto-add Member
        // because adulthood doesn't imply church membership. Admins grant
        // Member explicitly.
        $promotedToAdult = $this->promote(
            from: PersonNature::Teenager,
            to: null,
            ageMin: Person::TEENAGER_MAX_AGE + 1,
            ageMax: null,
            dry: $dry,
        );

        $this->info(($dry ? 'Would promote ' : 'Promoted ').$promotedToTeenager.' child(ren) → teenager.');
        $this->info(($dry ? 'Would promote ' : 'Promoted ').$promotedToAdult.' teenager(s) → adult.');

        return self::SUCCESS;
    }

    private function promote(
        PersonNature $from,
        ?PersonNature $to,
        int $ageMin,
        ?int $ageMax,
        bool $dry,
    ): int {
        // Birthdate window: people whose age sits in [ageMin, ageMax] today.
        // We use a date window keyed off today so a child whose birthday
        // already passed this year crosses the threshold the next time the
        // job runs.
        $today = now()->startOfDay();
        $maxBirthdate = $today->copy()->subYears($ageMin);
        $minBirthdate = $ageMax === null ? null : $today->copy()->subYears($ageMax + 1)->addDay();

        $q = Person::query()
            ->where('person_type', PersonType::Individual->value)
            ->whereNotNull('birthdate')
            ->where('birthdate', '<=', $maxBirthdate->toDateString())
            ->whereJsonContains('natures', $from->value);

        if ($minBirthdate) {
            $q->where('birthdate', '>=', $minBirthdate->toDateString());
        }

        $count = 0;
        $q->chunkById(100, function ($people) use ($from, $to, $dry, &$count) {
            foreach ($people as $person) {
                $person->removeNature($from);
                if ($to) {
                    $person->addNature($to);
                }
                if (! $dry) {
                    $person->save();
                }
                $count++;
            }
        });

        return $count;
    }
}
