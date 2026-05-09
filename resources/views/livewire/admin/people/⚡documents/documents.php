<?php

use App\Livewire\Forms\PersonDocumentForm;
use App\Models\Person;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public PersonDocumentForm $form;

    public Person $person;

    public bool $showModal = false;

    public $newImage = null;

    public function mount(int $personId): void
    {
        abort_unless(auth()->user()?->can('users.manage') || auth()->user()?->can('users.manage.local'), 403);
        $this->person = Person::findOrFail($personId);
    }

    #[Computed]
    public function documents()
    {
        return $this->person->documents()
            ->with('media')
            ->orderBy('document_type')
            ->orderByDesc('issued_at')
            ->get();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->newImage = null;
        $this->form->person_id = $this->person->id;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $document = $this->person->documents()->findOrFail($id);
        $this->form->setDocument($document);
        $this->newImage = null;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->person_id = $this->person->id;

        $this->validate([
            'newImage' => ['nullable', 'file', 'mimes:jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $document = $this->form->save();

        if ($this->newImage) {
            $document->clearMediaCollection('image');
            $document->addMedia($this->newImage->getRealPath())
                ->usingName($this->newImage->getClientOriginalName())
                ->usingFileName($this->newImage->getClientOriginalName())
                ->toMediaCollection('image');
        }

        $this->showModal = false;
        $this->form->reset();
        $this->newImage = null;
        unset($this->documents);
    }

    public function delete(int $id): void
    {
        $this->person->documents()->where('id', $id)->delete();
        unset($this->documents);
    }
};
