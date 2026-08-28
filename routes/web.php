<?php

use Illuminate\Support\Facades\Route;
Route::get('/',function(){
    return view('auth.login');
});
// ==================== STAFF PORTAL ====================
// Single shared panel for any non-admin (staff) login.
Route::middleware(['auth', 'client.scope'])->group(function () {
    Route::livewire('/portal/dashboard', 'pages::portal.dashboard')->name('portal.dashboard');
});

// ==================== CLIENT PORTAL ====================
// Read-only, company-scoped view for client logins (contacts.user_id).
Route::middleware(['auth', 'client.scope', 'client.visit'])->prefix('client')->name('client.')->group(function () {
    Route::livewire('/dashboard', 'pages::client.dashboard')->name('dashboard');
    Route::livewire('/projects', 'pages::client.projects')->name('projects');
    Route::livewire('/projects/{id}', 'pages::client.project-show')->name('project-show');
    Route::livewire('/estimates', 'pages::client.estimates')->name('estimates');
    Route::livewire('/estimates/{id}', 'pages::client.estimate-show')->name('estimate-show');
    Route::livewire('/quotations', 'pages::client.quotations')->name('quotations');
    Route::livewire('/quotations/{id}', 'pages::client.quotation-show')->name('quotation-show');
    Route::livewire('/payments', 'pages::client.payments')->name('payments');
    Route::livewire('/insights', 'pages::client.insights')->name('insights');
    Route::livewire('/updates', 'pages::client.updates')->name('updates');
    Route::livewire('/profile', 'pages::client.profile')->name('profile');
    Route::get('/estimates/{id}/pdf', [\App\Http\Controllers\Client\DocumentController::class, 'estimate'])->name('estimate-pdf');
    Route::get('/quotations/{id}/pdf', [\App\Http\Controllers\Client\DocumentController::class, 'quotation'])->name('quotation-pdf');
});

Route::middleware(['auth', 'module.access', 'client.scope', 'staff.presence'])->group(function(){
    Route::livewire('dashboard','pages::admin.dashboard')->name('dashboard');
    Route::livewire('assistant','pages::admin.agent')->name('assistant');
    Route::livewire('messages','pages::admin.messages.index')->name('messages.index');
Route::prefix('contacts')->group(function(){
    Route::livewire('/all','pages::admin.contacts.allcontacts')->name('contacts.all');
    Route::livewire('/add','pages::admin.contacts.add')->name('contacts.add');
    Route::livewire('/edit/{id}','pages::admin.contacts.edit')->name('contacts.edit');
    Route::livewire('/show/{id}','pages::admin.contacts.show')->name('contacts.show');
    Route::livewire('/groups','pages::admin.contacts.groups')->name('contacts.groups');
    Route::livewire('/import','pages::admin.contacts.import')->name('contacts.import');
});
// ==================== COMPANIES ====================
Route::prefix('companies')->name('companies.')->group(function(){
    Route::livewire('/all', 'pages::admin.companies.all')->name('all');
    Route::livewire('/show/{id}', 'pages::admin.companies.show')->name('show');
    Route::livewire('/edit/{id}', 'pages::admin.companies.edit')->name('edit');
    Route::livewire('/add', 'pages::admin.companies.add')->name('add');
});

// ==================== DEALS ====================
Route::prefix('deals')->name('deals.')->group(function(){
    Route::livewire('/pipeline', 'pages::admin.deals.pipeline')->name('pipeline');
    Route::livewire('/show/{id}', 'pages::admin.deals.show')->name('view');
    Route::livewire('/edit/{id}', 'pages::admin.deals.edit')->name('edit');
    Route::livewire('/all', 'pages::admin.deals.all')->name('all');
    Route::livewire('/add', 'pages::admin.deals.add')->name('add');
    Route::livewire('/lost', 'pages::admin.deals.lost')->name('lost');
});

// ==================== PROJECTS ====================
Route::prefix('projects')->name('projects.')->group(function(){
    Route::livewire('/all','pages::admin.projects.all')->name('all');
    Route::livewire('/add','pages::admin.projects.add')->name('add');
    Route::livewire('/edit/{id}','pages::admin.projects.edit')->name('edit');
    Route::livewire('/show/{id}','pages::admin.projects.show')->name('show');
    Route::livewire('/payments','pages::admin.projects.payments')->name('payments');
});

// ==================== TASKS ====================
Route::prefix('tasks')->name('tasks.')->group(function(){
    Route::livewire('/my', 'pages::admin.tasks.my')->name('my');
    Route::livewire('/all', 'pages::admin.tasks.all')->name('all');
    Route::livewire('/create', 'pages::admin.tasks.create')->name('create');
    Route::livewire('/completed', 'pages::admin.tasks.completed')->name('completed');
});
// ==================== BUGS (QA / Developer / Tech Lead) ====================
Route::prefix('bugs')->name('bugs.')->group(function(){
    Route::livewire('/all', 'pages::admin.bugs.all')->name('all');
    Route::livewire('/show/{id}', 'pages::admin.bugs.show')->name('show');
});
Route::prefix('staff')->name('staff.')->group(function(){
    Route::livewire('/create', 'pages::admin.staff.create')->name('create');
    Route::livewire('/all', 'pages::admin.staff.all')->name('all');
    Route::livewire('/add', 'pages::admin.staff.add')->name('add');
    Route::livewire('/edit/{id}', 'pages::admin.staff.edit')->name('edit');
    Route::livewire('/show/{id}', 'pages::admin.staff.show')->name('show');
    Route::livewire('/designations', 'pages::admin.staff.designations')->name('designations');
});
// ==================== CALENDAR ====================
Route::prefix('calendar')->name('calendar.')->group(function(){
    Route::livewire('/schedule', 'pages::admin.calendar.schedule')->name('schedule');
    Route::livewire('/events', 'pages::admin.calendar.events')->name('events');
});

// ==================== COMMUNICATIONS ====================
Route::prefix('communications')->name('communications.')->group(function(){
    Route::livewire('/emails', 'pages::admin.communications.emails')->name('emails');
    Route::livewire('/calls', 'pages::admin.communications.calls')->name('calls');
    Route::livewire('/meetings', 'pages::admin.communications.meetings')->name('meetings');
    Route::livewire('/activity-log', 'pages::admin.communications.activity-log')->name('activity-log');
});

// ==================== REPORTS ====================
Route::prefix('reports')->name('reports.')->group(function(){
    Route::livewire('/sales', 'pages::admin.reports.sales')->name('sales');
    Route::livewire('/activity', 'pages::admin.reports.activity')->name('activity');
    Route::livewire('/performance', 'pages::admin.reports.performance')->name('performance');
    Route::livewire('/client-attendance', 'pages::admin.reports.client-attendance')->name('client-attendance');
});

// ==================== ATTENDANCE ERP ====================
Route::prefix('attendance')->name('attendance.')->group(function(){
    Route::livewire('/', 'pages::admin.attendance.index')->name('index');
    Route::livewire('/{type}/{id}', 'pages::admin.attendance.person')->name('person');
});

// ==================== SERVICES ====================
Route::prefix('services')->name('services.')->group(function(){
    Route::livewire('/all', 'pages::admin.services.all')->name('all');
    Route::livewire('/add', 'pages::admin.services.add')->name('add');
    Route::livewire('/edit/{id}', 'pages::admin.services.edit')->name('edit');
    Route::livewire('/show/{id}', 'pages::admin.services.show')->name('show');
    Route::livewire('/categories', 'pages::admin.services.categories')->name('categories');
});

// ==================== PRODUCTS ====================
Route::prefix('products')->name('products.')->group(function(){
    Route::livewire('/all', 'pages::admin.products.all')->name('all');
    Route::livewire('/add', 'pages::admin.products.add')->name('add');
    Route::livewire('/edit/{id}', 'pages::admin.products.edit')->name('edit');
    Route::livewire('/show/{id}', 'pages::admin.products.show')->name('show');
    Route::livewire('/categories', 'pages::admin.products.categories')->name('categories');
});

// ==================== PORTFOLIO ====================
Route::prefix('portfolio')->name('portfolio.')->group(function(){
    Route::livewire('/all', 'pages::admin.portfolio.all')->name('all');
    Route::livewire('/add', 'pages::admin.portfolio.add')->name('add');
    Route::livewire('/edit/{id}', 'pages::admin.portfolio.edit')->name('edit');
    Route::livewire('/show/{id}', 'pages::admin.portfolio.show')->name('show');
});

// ==================== TESTIMONIALS ====================
Route::prefix('testimonials')->name('testimonials.')->group(function(){
    Route::livewire('/all', 'pages::admin.testimonials.all')->name('all');
    Route::livewire('/add', 'pages::admin.testimonials.add')->name('add');
    Route::livewire('/edit/{id}', 'pages::admin.testimonials.edit')->name('edit');
});

// ==================== ESTIMATES ====================
Route::prefix('estimates')->name('estimates.')->group(function(){
    Route::livewire('/all','pages::admin.estimates.all')->name('all');
    Route::livewire('/add','pages::admin.estimates.add')->name('add');
    Route::livewire('/edit/{id}','pages::admin.estimates.edit')->name('edit');
    Route::livewire('/show/{id}','pages::admin.estimates.show')->name('show');
});

// ==================== QUOTATIONS ====================
Route::prefix('quotations')->name('quotations.')->group(function(){
    Route::livewire('/all','pages::admin.quotations.all')->name('all');
    Route::livewire('/add','pages::admin.quotations.add')->name('add');
    Route::livewire('/show/{id}','pages::admin.quotations.show')->name('show');
});

// ==================== PRICING ====================
Route::prefix('pricing')->name('pricing.')->group(function(){
    Route::livewire('/all','pages::admin.pricing.all')->name('all');
    Route::livewire('/add','pages::admin.pricing.add')->name('add');
    Route::livewire('/edit/{id}','pages::admin.pricing.edit')->name('edit');
});

// ==================== BLOG ====================
Route::prefix('blog')->name('blog.')->group(function(){
    Route::livewire('/all','pages::admin.blog.all')->name('all');
    Route::livewire('/add','pages::admin.blog.add')->name('add');
    Route::livewire('/edit/{id}','pages::admin.blog.edit')->name('edit');
    Route::livewire('/show/{id}','pages::admin.blog.show')->name('show');
    Route::livewire('/categories','pages::admin.blog.categories')->name('categories');
});

// ==================== SETTINGS ====================
Route::prefix('settings')->name('settings.')->group(function(){
    Route::livewire('/general', 'pages::admin.settings.general')->name('general');
    Route::livewire('/user-management', 'pages::admin.settings.user-management')->name('user-management');
    Route::livewire('/roles-permissions', 'pages::admin.settings.roles-permissions')->name('roles-permissions');
    Route::livewire('/payment-gateways', 'pages::admin.settings.payment-gateways')->name('payment-gateways');
});
});
// direct route ...

// authentication route ..
// Registration is admin-only (staff logins via staff/show, client logins via
// contacts/show) — self-service sign-up is intentionally disabled.
Auth::routes(['register' => false]);

// Lightweight presence heartbeat — the browser pings this only while the tab
// is actually visible, so "online now" reflects real activity, not a stale
// page load.
Route::post('/heartbeat', function () {
    $user = auth()->user();
    if ($user && ! in_array($user->role, ['client', 'admin'], true)) {
        $staffId = \App\Models\staff::where('user_id', $user->id)->value('id');
        if ($staffId) {
            try {
                \App\Models\AttendanceRecord::recordStaffActivity((int) $staffId); // ensures row + accrues active minutes
            } catch (\Throwable $e) {
                \App\Models\AttendanceRecord::accrueActive((int) $staffId);
            }
        }
    }
    return response()->noContent();
})->middleware('auth')->name('heartbeat');
