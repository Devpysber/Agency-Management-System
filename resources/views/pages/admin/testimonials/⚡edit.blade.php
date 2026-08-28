<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $testimonialId;
    public $client_name;
    public $company;
    public $message;
    public $rating = 5;
    public $status = 'pending';
    public $photo;
    public $existingAvatar;

    protected $rules = [
        'client_name' => 'required|string|max:255',
        'company' => 'nullable|string|max:255',
        'message' => 'required|string',
        'rating' => 'required|integer|min:1|max:5',
        'status' => 'required|in:pending,approved',
        'photo' => 'nullable|image|max:2048',
    ];

    public function mount($id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $this->testimonialId = $testimonial->id;
        $this->client_name = $testimonial->client_name;
        $this->company = $testimonial->company;
        $this->message = $testimonial->message;
        $this->rating = $testimonial->rating;
        $this->status = $testimonial->status;
        $this->existingAvatar = $testimonial->avatar;
    }

    public function update()
    {
        $this->validate();

        $testimonial = Testimonial::findOrFail($this->testimonialId);
        $testimonial->client_name = $this->client_name;
        $testimonial->company = $this->company;
        $testimonial->message = $this->message;
        $testimonial->rating = $this->rating;
        $testimonial->status = $this->status ?: 'pending';

        if ($this->photo) {
            if ($testimonial->avatar) {
                Storage::disk('public')->delete($testimonial->avatar);
            }
            $testimonial->avatar = $this->photo->store('testimonials', 'public');
        }

        $testimonial->save();

        session()->flash('success', 'Testimonial updated successfully');

        return redirect()->route('testimonials.all');
    }

    public function render()
    {
        return $this->view([]);
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Edit Testimonial</h1>
                <p>Update the testimonial details.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('testimonials.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Testimonials
                </a>
                <button type="button" form="testimonialEditForm" wire:click="update" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Update Testimonial
                </button>
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

        <!-- Testimonial Form -->
        <div class="card">
            <div class="card-body">
                <form id="testimonialEditForm">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-user me-1 text-muted"></i>
                                        Client Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('client_name') is-invalid @enderror" wire:model="client_name" placeholder="Enter client name">
                                    @error('client_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-building me-1 text-muted"></i>
                                        Company
                                    </label>
                                    <input type="text" class="form-control" wire:model="company" placeholder="Enter company name">
                                    @error('company')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-star me-1 text-muted"></i>
                                        Rating
                                    </label>
                                    <select class="form-select" wire:model="rating">
                                        <option value="5">⭐⭐⭐⭐⭐ 5 Stars</option>
                                        <option value="4">⭐⭐⭐⭐ 4 Stars</option>
                                        <option value="3">⭐⭐⭐ 3 Stars</option>
                                        <option value="2">⭐⭐ 2 Stars</option>
                                        <option value="1">⭐ 1 Star</option>
                                    </select>
                                    @error('rating')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-circle me-1 text-muted"></i>
                                        Status
                                    </label>
                                    <select class="form-select" wire:model="status">
                                        <option value="pending">🟡 Pending</option>
                                        <option value="approved">🟢 Approved</option>
                                    </select>
                                    @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-align-left me-1 text-muted"></i>
                                        Testimonial Message <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" wire:model="message" rows="5" placeholder="Enter the client's testimonial..."></textarea>
                                    @error('message')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-image me-2"></i>
                                        Client Avatar
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" class="img-fluid rounded-circle mb-3" alt="Preview">
                                    @elseif ($existingAvatar)
                                        <img src="{{ asset('storage/' . $existingAvatar) }}" class="img-fluid rounded-circle mb-3" alt="Current avatar">
                                    @endif
                                    <input type="file" class="form-control @error('photo') is-invalid @enderror" wire:model="photo">
                                    @error('photo')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
