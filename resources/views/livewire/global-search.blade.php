<div>
    <!-- Trigger Shortcut Listener (Ctrl+K or Cmd+K) -->
    <div @keydown.window.prevent.ctrl.k="$wire.open()"
         @keydown.window.prevent.cmd.k="$wire.open()"
         @keydown.escape.window="$wire.close()">
    </div>

    @if($isOpen)
        <div class="gs-modal-backdrop" wire:click="close">
            <div class="gs-modal" @click.stop>
                <div class="gs-input-wrap">
                    <i class="fas fa-search gs-icon"></i>
                    <input type="text"
                           class="gs-input"
                           wire:model.live.debounce.250ms="query"
                           placeholder="Type to search contacts, companies, deals, projects, tasks... (Esc to close)"
                           autofocus>
                    <span class="gs-badge-kbd">ESC</span>
                </div>

                <div class="gs-body">
                    @if(strlen(trim($query)) < 2)
                        <div class="gs-tip">
                            <i class="fas fa-keyboard me-2"></i> Type at least 2 characters to search across the entire system.
                        </div>
                    @elseif($totalMatches === 0)
                        <div class="gs-empty">
                            <i class="fas fa-face-meh me-2"></i> No records found matching "<strong>{{ $query }}</strong>".
                        </div>
                    @else
                        <!-- Contacts -->
                        @if($results['contacts']->isNotEmpty())
                            <div class="gs-group-title"><i class="fas fa-address-book me-2"></i> Contacts</div>
                            <div class="gs-list">
                                @foreach($results['contacts'] as $contact)
                                    <a href="{{ route('contacts.show', $contact->id) }}" wire:navigate class="gs-item" wire:click="close">
                                        <div class="gs-item-icon blue"><i class="fas fa-user"></i></div>
                                        <div class="gs-item-content">
                                            <strong>{{ $contact->first_name }} {{ $contact->last_name }}</strong>
                                            <span>{{ $contact->email ?: 'No email' }}</span>
                                        </div>
                                        <i class="fas fa-arrow-right gs-arrow"></i>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Companies -->
                        @if($results['companies']->isNotEmpty())
                            <div class="gs-group-title"><i class="fas fa-building me-2"></i> Companies</div>
                            <div class="gs-list">
                                @foreach($results['companies'] as $comp)
                                    <a href="{{ route('companies.show', $comp->id) }}" wire:navigate class="gs-item" wire:click="close">
                                        <div class="gs-item-icon green"><i class="fas fa-building"></i></div>
                                        <div class="gs-item-content">
                                            <strong>{{ $comp->company_name }}</strong>
                                            <span>{{ $comp->company_email ?: 'Account' }}</span>
                                        </div>
                                        <i class="fas fa-arrow-right gs-arrow"></i>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Deals -->
                        @if($results['deals']->isNotEmpty())
                            <div class="gs-group-title"><i class="fas fa-chart-line me-2"></i> Deals</div>
                            <div class="gs-list">
                                @foreach($results['deals'] as $deal)
                                    <a href="{{ route('deals.view', $deal->id) }}" wire:navigate class="gs-item" wire:click="close">
                                        <div class="gs-item-icon orange"><i class="fas fa-file-invoice-dollar"></i></div>
                                        <div class="gs-item-content">
                                            <strong>{{ $deal->deal_name }}</strong>
                                            <span>Stage: {{ ucfirst($deal->stage) }} · ${{ number_format($deal->value ?? 0, 2) }}</span>
                                        </div>
                                        <i class="fas fa-arrow-right gs-arrow"></i>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Projects -->
                        @if($results['projects']->isNotEmpty())
                            <div class="gs-group-title"><i class="fas fa-diagram-project me-2"></i> Projects</div>
                            <div class="gs-list">
                                @foreach($results['projects'] as $proj)
                                    <a href="{{ route('projects.show', $proj->id) }}" wire:navigate class="gs-item" wire:click="close">
                                        <div class="gs-item-icon purple"><i class="fas fa-diagram-project"></i></div>
                                        <div class="gs-item-content">
                                            <strong>{{ $proj->name }}</strong>
                                            <span>{{ ucfirst(str_replace('_', ' ', $proj->status)) }} · {{ $proj->progress }}%</span>
                                        </div>
                                        <i class="fas fa-arrow-right gs-arrow"></i>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Tasks -->
                        @if($results['tasks']->isNotEmpty())
                            <div class="gs-group-title"><i class="fas fa-tasks me-2"></i> Tasks</div>
                            <div class="gs-list">
                                @foreach($results['tasks'] as $task)
                                    <a href="{{ route('tasks.all') }}" wire:navigate class="gs-item" wire:click="close">
                                        <div class="gs-item-icon teal"><i class="fas fa-check"></i></div>
                                        <div class="gs-item-content">
                                            <strong>{{ $task->title }}</strong>
                                            <span>Priority: {{ ucfirst($task->priority) }} · Status: {{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                                        </div>
                                        <i class="fas fa-arrow-right gs-arrow"></i>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Staff -->
                        @if($results['staff']->isNotEmpty())
                            <div class="gs-group-title"><i class="fas fa-users me-2"></i> Team / Staff</div>
                            <div class="gs-list">
                                @foreach($results['staff'] as $stf)
                                    <a href="{{ route('staff.show', $stf->id) }}" wire:navigate class="gs-item" wire:click="close">
                                        <div class="gs-item-icon red"><i class="fas fa-id-badge"></i></div>
                                        <div class="gs-item-content">
                                            <strong>{{ $stf->name }}</strong>
                                            <span>{{ $stf->designation ?: 'Staff Member' }}</span>
                                        </div>
                                        <i class="fas fa-arrow-right gs-arrow"></i>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>

                <div class="gs-footer">
                    <span class="text-muted"><kbd>↑</kbd> <kbd>↓</kbd> to navigate</span>
                    <span class="text-muted"><kbd>ESC</kbd> to dismiss</span>
                </div>
            </div>
        </div>
    @endif

    <style>
        .gs-modal-backdrop {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px); z-index: 9999;
            display: flex; align-items: flex-start; justify-content: center;
            padding: 80px 16px 20px;
        }
        .gs-modal {
            width: 100%; max-width: 640px; background: #ffffff;
            border-radius: 14px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden; border: 1px solid #e2e8f0;
            display: flex; flex-direction: column; max-height: 80vh;
        }
        .gs-input-wrap {
            display: flex; align-items: center; padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9; gap: 12px;
        }
        .gs-icon { font-size: 18px; color: #94a3b8; }
        .gs-input {
            flex: 1; border: none; outline: none; font-size: 16px; color: #0f172a;
            background: transparent;
        }
        .gs-badge-kbd {
            font-size: 11px; font-weight: 700; color: #64748b; background: #f1f5f9;
            padding: 3px 6px; border-radius: 6px; border: 1px solid #e2e8f0;
        }
        .gs-body { padding: 12px; overflow-y: auto; flex: 1; }
        .gs-tip { padding: 32px 16px; text-align: center; color: #64748b; font-size: 14px; }
        .gs-empty { padding: 32px 16px; text-align: center; color: #94a3b8; font-size: 14px; }
        .gs-group-title {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .05em; color: #64748b; padding: 10px 12px 6px;
        }
        .gs-list { display: flex; flex-direction: column; gap: 4px; margin-bottom: 10px; }
        .gs-item {
            display: flex; align-items: center; gap: 12px; padding: 10px 12px;
            border-radius: 10px; text-decoration: none; color: #1e293b;
            transition: background 0.15s;
        }
        .gs-item:hover { background: #f8fafc; color: #0f172a; }
        .gs-item-icon {
            width: 34px; height: 34px; border-radius: 8px; display: flex;
            align-items: center; justify-content: center; font-size: 14px; color: #ffffff;
            flex-shrink: 0;
        }
        .gs-item-icon.blue { background: #3b82f6; }
        .gs-item-icon.green { background: #10b981; }
        .gs-item-icon.orange { background: #f59e0b; }
        .gs-item-icon.purple { background: #8b5cf6; }
        .gs-item-icon.teal { background: #14b8a6; }
        .gs-item-icon.red { background: #ef4444; }
        .gs-item-content { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .gs-item-content strong { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .gs-item-content span { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .gs-arrow { font-size: 12px; color: #cbd5e1; }
        .gs-footer {
            padding: 10px 18px; background: #f8fafc; border-top: 1px solid #f1f5f9;
            display: flex; justify-content: space-between; font-size: 11.5px;
        }
        .gs-footer kbd {
            background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px;
            padding: 1px 4px; font-size: 10.5px; color: #475569;
        }

        /* Dark Mode Support for Global Search */
        :root[data-theme="dark"] .gs-modal { background: #171a22; border-color: #2a2f3c; }
        :root[data-theme="dark"] .gs-input-wrap { border-bottom-color: #2a2f3c; }
        :root[data-theme="dark"] .gs-input { color: #f1f5f9; }
        :root[data-theme="dark"] .gs-badge-kbd { background: #1f232e; border-color: #2a2f3c; color: #94a3b8; }
        :root[data-theme="dark"] .gs-item { color: #e2e8f0; }
        :root[data-theme="dark"] .gs-item:hover { background: #1f232e; }
        :root[data-theme="dark"] .gs-footer { background: #1f232e; border-top-color: #2a2f3c; }
        :root[data-theme="dark"] .gs-footer kbd { background: #262b38; border-color: #2a2f3c; color: #cbd5e1; }
    </style>
</div>
