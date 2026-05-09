<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\PersonDocument;
use Livewire\Form;

class PersonDocumentForm extends Form
{
    public ?PersonDocument $document = null;

    public ?int $person_id = null;

    public string $document_type = '';

    public string $number = '';

    public string $issuer = '';

    public string $issued_at = '';

    public string $expires_at = '';

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'document_type' => ['required', 'string', 'max:32'],
            'number' => ['nullable', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
        ];
    }

    public function setDocument(PersonDocument $document): void
    {
        $this->document = $document;
        $this->person_id = $document->person_id;
        $this->document_type = $document->document_type;
        $this->number = $document->number ?? '';
        $this->issuer = $document->issuer ?? '';
        $this->issued_at = $document->issued_at?->format('Y-m-d') ?? '';
        $this->expires_at = $document->expires_at?->format('Y-m-d') ?? '';
    }

    public function save(): PersonDocument
    {
        $data = $this->validate();
        foreach (['number', 'issuer', 'issued_at', 'expires_at'] as $k) {
            if (($data[$k] ?? null) === '') {
                $data[$k] = null;
            }
        }

        if ($this->document) {
            $this->document->update($data);
        } else {
            $this->document = PersonDocument::create($data);
        }

        return $this->document;
    }
}
