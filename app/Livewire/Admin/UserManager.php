<?php

namespace App\Livewire\Admin;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SSOService;
use App\Services\SubscriptionService;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_admin = false;

    // Subscriptions Modal State
    public bool $showSubModal = false;

    public ?int $manageSubUserId = null;

    public ?int $selectedPlanId = null;

    // Secure Delete State
    public bool $showDeleteModal = false;

    public string $deleteType = ''; // 'user' or 'subscription'

    public ?int $deleteTargetId = null;

    public string $deleteTargetExpected = '';

    public string $deleteVerificationInput = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openModal(?int $userId = null)
    {
        $this->resetValidation();
        $this->reset(['name', 'email', 'password', 'is_admin']);
        $this->editingUserId = $userId;

        if ($userId) {
            $user = User::findOrFail($userId);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->is_admin = $user->is_admin;
        }

        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->editingUserId),
            ],
            'password' => $this->editingUserId ? 'nullable|string|min:8' : 'required|string|min:8',
            'is_admin' => 'boolean',
        ];

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
        ];

        if (! empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            User::findOrFail($this->editingUserId)->update($data);
            Flux::toast(text: 'User updated successfully.', variant: 'success');
        } else {
            User::create($data);
            Flux::toast(text: 'User created successfully.', variant: 'success');
        }

        $this->showModal = false;
    }

    public function confirmDeleteUser(int $userId)
    {
        $user = User::findOrFail($userId);
        $this->deleteType = 'user';
        $this->deleteTargetId = $user->id;
        $this->deleteTargetExpected = $user->email;
        $this->deleteVerificationInput = '';
        $this->showDeleteModal = true;
    }

    public function openSubModal(int $userId)
    {
        $this->manageSubUserId = $userId;
        $this->selectedPlanId = null;
        $this->showSubModal = true;
    }

    public function assignSubscription()
    {
        $this->validate([
            'selectedPlanId' => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($this->selectedPlanId);
        $user = User::findOrFail($this->manageSubUserId);

        $expiredAt = $this->calculateExpiry();

        Subscription::create([
            'user_id' => $user->id,
            'external_id' => 'manual_'.Str::random(8),
            'plan_name' => $plan->name,
            'max_server' => $plan->max_server,
            'status' => 'active',
            'expired_at' => $expiredAt,
        ]);

        $this->selectedPlanId = null;
        Flux::toast(text: "Plan {$plan->name} assigned to {$user->name}.", variant: 'success');
    }

    public function renewSubscription(int $subId)
    {
        $sub = Subscription::where('id', $subId)->where('user_id', $this->manageSubUserId)->firstOrFail();

        $expiredAt = $this->calculateExpiry();
        $wasAlreadyActive = $sub->status === 'active';

        $sub->update([
            'status' => 'active',
            'expired_at' => $expiredAt,
        ]);

        // If status was already 'active' (expired but not yet suspended by cron),
        // the observer won't fire because status didn't change. Manually restore servers.
        if ($wasAlreadyActive) {
            app(SubscriptionService::class)->restoreServers($sub);
        }

        Flux::toast(text: 'Subscription renewed successfully. Suspended servers are being restored.', variant: 'success');
    }

    /**
     * Calculate the expiry date based on the system's subscription duration setting.
     * Uses CarbonImmutable-safe chaining to avoid mutation bugs.
     */
    protected function calculateExpiry(): CarbonImmutable
    {
        $duration = Setting::get('subscription_duration_default', ['value' => 30, 'unit' => 'days']);
        if (! is_array($duration)) {
            $duration = ['value' => 30, 'unit' => 'days'];
        }
        $val = max(1, (int) ($duration['value'] ?? 30));
        $unit = $duration['unit'] ?? 'days';

        return match ($unit) {
            'months' => now()->addMonths($val),
            'years' => now()->addYears($val),
            default => now()->addDays($val),
        };
    }

    public function confirmRevokeSubscription(int $subId)
    {
        $sub = Subscription::where('id', $subId)->where('user_id', $this->manageSubUserId)->firstOrFail();
        $this->deleteType = 'subscription';
        $this->deleteTargetId = $sub->id;
        $this->deleteTargetExpected = 'REVOKE';
        $this->deleteVerificationInput = '';
        $this->showDeleteModal = true;
        $this->showSubModal = false; // Hide parent modal temporarily
    }

    public function executeDelete()
    {
        if ($this->deleteVerificationInput !== $this->deleteTargetExpected) {
            $this->addError('deleteVerificationInput', 'Verification phrase does not match.');

            return;
        }

        if ($this->deleteType === 'user') {
            $user = User::findOrFail($this->deleteTargetId);
            if ($user->id === Auth::id()) {
                $this->addError('deleteVerificationInput', 'You cannot delete your own account.');

                return;
            }
            if ($user->servers()->exists()) {
                $this->addError('deleteVerificationInput', 'Cannot delete user with active servers. Terminate servers first.');

                return;
            }
            $user->subscriptions()->delete();
            $user->delete();
            Flux::toast(text: "User {$user->name} has been deleted.", variant: 'success');

        } elseif ($this->deleteType === 'subscription') {
            $sub = Subscription::where('id', $this->deleteTargetId)->firstOrFail();
            if ($sub->servers()->exists()) {
                $this->addError('deleteVerificationInput', 'Cannot revoke. Terminate user proxy servers on this subscription first.');

                return;
            }
            $sub->delete();
            Flux::toast(text: 'Subscription revoked successfully.', variant: 'success');
            // Reopen the subscription management modal implicitly
            $this->showSubModal = true;
        }

        $this->showDeleteModal = false;
    }

    public function generateSSOLink(int $userId, SSOService $ssoService)
    {
        $user = User::findOrFail($userId);

        if ($user->id === Auth::id()) {
            Flux::toast(text: 'You are already logged in as yourself.', variant: 'warning');

            return;
        }

        $token = $ssoService->generateToken($user, Auth::id());

        // Return a redirect to the SSO login route
        return redirect()->route('sso.login', ['token' => $token]);
    }

    public function render()
    {
        $users = User::withCount(['servers', 'subscriptions'])
            ->where('name', 'like', '%'.$this->search.'%')
            ->orWhere('email', 'like', '%'.$this->search.'%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $manageUser = $this->manageSubUserId ? User::with('subscriptions')->find($this->manageSubUserId) : null;
        $plans = Plan::all();

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'manageUser' => $manageUser,
            'plans' => $plans,
        ]);
    }
}
