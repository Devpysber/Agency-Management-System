<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\pagesController;
Route::get('/',function(){
    return view('auth.login');
});
Route::middleware('auth')->group(function(){
    Route::livewire('dashboard','pages::admin.dashboard')->name('dashboard');
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

// ==================== TASKS ====================
Route::prefix('tasks')->name('tasks.')->group(function(){
    Route::livewire('/my', 'pages::admin.tasks.my')->name('my');
    Route::livewire('/all', 'pages::admin.tasks.all')->name('all');
    Route::livewire('/create', 'pages::admin.tasks.create')->name('create');
    Route::livewire('/completed', 'pages::admin.tasks.completed')->name('completed');
});
Route::prefix('staff')->name('staff.')->group(function(){
    Route::livewire('/create', 'pages::admin.staff.create')->name('create');
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
});

// ==================== SETTINGS ====================
Route::prefix('settings')->name('settings.')->group(function(){
    Route::livewire('/general', 'pages::admin.settings.general')->name('general');
    Route::livewire('/user-management', 'pages::admin.settings.user-management')->name('user-management');
    Route::livewire('/roles-permissions', 'pages::admin.settings.roles-permissions')->name('roles-permissions');
});
});
// direct route ...

// authentication route ..
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
