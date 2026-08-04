@extends('layouts.admin')

@section('content')
<style>
    /* Premium Action Button */
    .premium-action-btn {
        background: linear-gradient(135deg, var(--garuda-dark), #1a1a1a);
        color: var(--garuda-gold) !important;
        border: 1px solid rgba(200, 155, 60, 0.4);
        border-radius: 8px;
        padding: 6px 14px;
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    .premium-action-btn:hover, .premium-action-btn:focus {
        background: linear-gradient(135deg, #1a1a1a, var(--garuda-dark));
        border-color: var(--garuda-gold);
        box-shadow: 0 4px 12px rgba(200, 155, 60, 0.2);
        transform: translateY(-1px);
    }
    
    .premium-delete-btn {
        border-radius: 8px;
        padding: 6px 10px;
        transition: all 0.3s ease;
    }

    /* Premium Dropdown Menu */
    .premium-dropdown {
        background: #ffffff;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 12px;
        padding: 8px;
        min-width: 240px;
    }
    
    .premium-dropdown .dropdown-item {
        border-radius: 8px;
        padding: 10px 15px;
        margin-bottom: 2px;
        transition: all 0.2s ease;
        background: transparent;
    }
    
    .premium-dropdown .dropdown-item:hover {
        background: rgba(200, 155, 60, 0.05);
        transform: translateX(4px);
    }
    
    .premium-dropdown .icon-box {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(0,0,0,0.03);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 14px;
    }
    
    .premium-dropdown .dropdown-item:hover .icon-box {
        background: #ffffff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .premium-dropdown .action-revoke {
        color: #e74c3c;
        font-weight: 600;
    }
    .premium-dropdown .action-revoke:hover {
        background: rgba(231, 76, 60, 0.05);
    }
</style>

<div class="content-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 style="color: var(--garuda-gold); font-weight: 600;">User & Role Management</h2>
            <p class="text-muted">Manage all registered accounts and assign administrative roles.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="card premium-card">
        <div class="card-body p-0">
            <div class="table-responsive" style="min-height: 350px; overflow-x: visible;">
                <table class="table garuda-table mb-0">
                    <thead>
                        <tr>
                            <th>User Details</th>
                            <th>Current Role / Status</th>
                            <th>Registered On</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="width:40px; height:40px; border-radius:50%; background:var(--garuda-dark); display:flex; align-items:center; justify-content:center; color:var(--garuda-gold);">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="color: var(--garuda-white);">{{ $user->name }}</div>
                                        <div class="text-muted small">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($user->roles->count() > 0)
                                    @foreach($user->roles as $role)
                                        @if($role->name === 'Super Admin')
                                            <span class="badge" style="background: rgba(200, 155, 60, 0.2); color: var(--garuda-gold); border: 1px solid var(--garuda-gold);">Super Admin</span>
                                        @elseif($role->name === 'Operator')
                                            <span class="badge" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71;">Operator</span>
                                        @elseif($role->name === 'User')
                                            <span class="badge" style="background: rgba(52, 152, 219, 0.2); color: #3498db; border: 1px solid #3498db;">Regular User</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $role->name }}</span>
                                        @endif
                                    @endforeach
                                @else
                                    <span class="badge" style="background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c;">Pending Approval</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted"><i class="far fa-calendar-alt me-1"></i> {{ $user->created_at->format('d M Y') }}</span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm dropdown-toggle premium-action-btn" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-shield-alt me-1"></i> Manage Access
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg premium-dropdown">
                                        <li>
                                            <form action="{{ route('users.updateRole', $user) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="role" value="Super Admin">
                                                <button type="submit" class="dropdown-item">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-box text-warning"><i class="fas fa-crown"></i></div>
                                                        <div>
                                                            <div class="fw-bold">Super Admin</div>
                                                            <small class="text-muted" style="font-size: 11px;">Full system access</small>
                                                        </div>
                                                    </div>
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('users.updateRole', $user) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="role" value="Operator">
                                                <button type="submit" class="dropdown-item">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-box text-success"><i class="fas fa-user-shield"></i></div>
                                                        <div>
                                                            <div class="fw-bold">Operator</div>
                                                            <small class="text-muted" style="font-size: 11px;">Manage standard modules</small>
                                                        </div>
                                                    </div>
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('users.updateRole', $user) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="role" value="User">
                                                <button type="submit" class="dropdown-item">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-box text-primary"><i class="fas fa-user"></i></div>
                                                        <div>
                                                            <div class="fw-bold">Regular User</div>
                                                            <small class="text-muted" style="font-size: 11px;">Devotees module only</small>
                                                        </div>
                                                    </div>
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('users.updateRole', $user) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="role" value="none">
                                                <button type="submit" class="dropdown-item action-revoke">
                                                    <i class="fas fa-ban me-2"></i> Revoke Access (Pending)
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                                
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline-block ms-2" onsubmit="return confirm('Are you sure you want to permanently delete this user?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger premium-delete-btn" title="Delete User"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-users mb-3" style="font-size: 32px; color: rgba(255,255,255,0.1);"></i><br>
                                No users found in the system.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
