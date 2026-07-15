<?php

namespace App\Livewire\Vendor;

use App\Services\ImageClarityChecker;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
class IdentityVerification extends Component
{
    use WithFileUploads;

    public string $idDocumentType = 'national_id';

    public $idDocument;

    public $selfie;

    public ?string $idDocumentClarityWarning = null;

    public ?string $selfieClarityWarning = null;

    public function updatedIdDocument(ImageClarityChecker $checker): void
    {
        $this->idDocumentClarityWarning = ($this->idDocument && ! $checker->isClear($this->idDocument->getRealPath()))
            ? 'This photo looks blurry or unclear. Please retake it in good lighting.'
            : null;
    }

    public function updatedSelfie(ImageClarityChecker $checker): void
    {
        $this->selfieClarityWarning = ($this->selfie && ! $checker->isClear($this->selfie->getRealPath()))
            ? 'This photo looks blurry or unclear. Please retake it in good lighting.'
            : null;
    }

    public function resubmitIdDocument(): void
    {
        $this->validate([
            'idDocumentType' => ['required', 'in:national_id,passport'],
            'idDocument' => ['required', 'image', 'max:5120'],
        ]);

        Auth::user()->vendor->update([
            'id_document_type' => $this->idDocumentType,
            'id_document_path' => $this->idDocument->store('vendor-kyc', 'local'),
            'id_document_rejection_reason' => null,
        ]);

        $this->reset(['idDocument', 'idDocumentClarityWarning']);
    }

    public function resubmitSelfie(): void
    {
        $this->validate([
            'selfie' => ['required', 'image', 'max:5120'],
        ]);

        Auth::user()->vendor->update([
            'selfie_path' => $this->selfie->store('vendor-kyc', 'local'),
            'selfie_rejection_reason' => null,
        ]);

        $this->reset(['selfie', 'selfieClarityWarning']);
    }

    public function render()
    {
        return view('livewire.vendor.identity-verification', [
            'vendor' => Auth::user()->vendor,
        ]);
    }
}
