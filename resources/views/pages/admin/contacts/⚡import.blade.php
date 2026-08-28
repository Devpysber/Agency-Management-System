<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Contact;
use App\Models\company;

new class extends Component
{
    use WithFileUploads;

    public $csv_file;
    public $importedCount = 0;
    public $skippedCount = 0;
    public $showResults = false;

    public function import()
    {
        if (!auth()->user()->hasPermission('Contacts', 'Create')) {
            session()->flash('error', "You don't have permission to import contacts.");
            return;
        }

        $this->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $this->csv_file->getRealPath();

        $handle = fopen($path, 'r');
        if ($handle === false) {
            session()->flash('error', 'Unable to read the uploaded file.');
            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            session()->flash('error', 'The CSV file appears to be empty.');
            return;
        }

        // Normalize headers for case-insensitive matching
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $nameIndex = array_search('name', $header);
        $emailIndex = array_search('email', $header);
        $phoneIndex = array_search('phone', $header);
        $companyIndex = array_search('company_name', $header) !== false
            ? array_search('company_name', $header)
            : array_search('company', $header);

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip completely blank lines
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $name = $nameIndex !== false ? trim((string) ($row[$nameIndex] ?? '')) : '';
            $email = $emailIndex !== false ? trim((string) ($row[$emailIndex] ?? '')) : '';
            $phone = $phoneIndex !== false ? trim((string) ($row[$phoneIndex] ?? '')) : null;
            $companyName = $companyIndex !== false ? trim((string) ($row[$companyIndex] ?? '')) : null;

            if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            if (Contact::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            $nameParts = preg_split('/\s+/', $name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            $companyId = null;
            if (!empty($companyName)) {
                $company = company::where('company_name', $companyName)->first();
                $companyId = $company?->id;
            }

            Contact::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'company_id' => $companyId,
                'lead_status' => 'new',
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            $imported++;
        }

        fclose($handle);

        $this->importedCount = $imported;
        $this->skippedCount = $skipped;
        $this->showResults = true;
        $this->reset('csv_file');

        session()->flash('success', "Imported {$imported} contacts, skipped {$skipped} rows.");
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Import Contacts</h1>
                <p>Bulk import contacts into your CRM from a CSV file.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('contacts.all') }}" class="btn btn-secondary">
                    <i class="fas fa-address-book"></i> All Contacts
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert-flash alert-flash-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert-flash alert-flash-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-csv me-2"></i>
                            Upload CSV File
                        </h3>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="import">
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-upload me-1 text-muted"></i>
                                    CSV File
                                </label>
                                <input type="file" class="form-control @error('csv_file') is-invalid @enderror"
                                       wire:model="csv_file" accept=".csv,.txt">
                                @error('csv_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div wire:loading wire:target="csv_file" class="text-muted small mt-1">
                                    <i class="fas fa-spinner fa-spin"></i> Uploading...
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="import">
                                <span wire:loading.remove wire:target="import">
                                    <i class="fas fa-file-import"></i> Import Contacts
                                </span>
                                <span wire:loading wire:target="import">
                                    <i class="fas fa-spinner fa-spin"></i> Importing...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                @if ($showResults)
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="stat-card">
                                <div class="stat-icon green">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Imported</h3>
                                    <p class="stat-number">{{ $importedCount }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <div class="stat-icon orange">
                                    <i class="fas fa-forward"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Skipped</h3>
                                    <p class="stat-number">{{ $skippedCount }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle me-2"></i>
                            CSV Format
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Your CSV should include a header row with the following column names (case-insensitive):</p>
                        <ul class="mb-3">
                            <li><code>name</code> &mdash; required</li>
                            <li><code>email</code> &mdash; required, must be a valid email</li>
                            <li><code>phone</code> &mdash; optional</li>
                            <li><code>company_name</code> &mdash; optional, matched against existing companies</li>
                        </ul>
                        <p class="text-muted mb-0">Rows missing a name or a valid email will be skipped and counted separately from the import.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
