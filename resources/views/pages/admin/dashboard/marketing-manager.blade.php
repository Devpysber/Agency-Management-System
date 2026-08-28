<!-- Marketing Dashboard -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-briefcase"></i></div>
        <div class="stat-info"><h3>Published Portfolio</h3><p class="stat-number">{{ $publishedPortfolio }}</p>
            <span class="stat-change">{{ $draftPortfolio }} in draft</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-quote-left"></i></div>
        <div class="stat-info"><h3>Testimonials</h3><p class="stat-number">{{ $totalTestimonials }}</p>
            <span class="stat-change">{{ $pendingTestimonials }} pending approval</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-newspaper"></i></div>
        <div class="stat-info"><h3>Blog Posts</h3><p class="stat-number">{{ $publishedBlogPosts }}</p>
            <span class="stat-change">of {{ $totalBlogPosts }} total</span></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-briefcase me-2"></i> Portfolio</h3>
                <a href="{{ route('portfolio.all') }}" class="view-all">Manage</a></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-newspaper me-2"></i> Blog &amp; Testimonials</h3>
                <a href="{{ route('blog.all') }}" class="view-all">Manage Blog</a></div>
        </div>
    </div>
</div>

<div class="card mb-4" style="border:1px solid #e5e7eb;">
    <div class="card-body">
        <h6 class="fw-semibold mb-2"><i class="fas fa-circle-info text-muted me-1"></i> Not built yet — flagged, not faked</h6>
        <p class="text-muted small mb-0">
            No Campaign, Leads-Generated, Social Media, SEO, Advertising, Marketing Spend, ROI, or Campaign
            Performance data model exists in this schema. This dashboard shows the real marketing content
            that exists (Portfolio/Testimonials/Blog) and Communications as the closest activity proxy.
            Say if you want the campaign/spend/ROI subsystem built — it's genuinely new tables, not a
            permissions problem.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Recent Content</h3></div>
    <div class="card-body p-0">
        @forelse ($recentContent as $item)
            <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <span>{{ $item->title ?? $item->name ?? 'Untitled' }}</span>
                <small class="text-muted">{{ $item->created_at->diffForHumans() }}</small>
            </div>
        @empty
            <p class="text-muted mb-0 p-3">No content yet.</p>
        @endforelse
    </div>
</div>
