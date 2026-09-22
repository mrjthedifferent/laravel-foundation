<?php

namespace Modules\Otp\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Otp\Actions\StoreOtpWhitelistAction;
use Modules\Otp\Actions\UpdateOtpWhitelistAction;
use Modules\Otp\Http\Requests\StoreOtpWhitelistRequest;
use Modules\Otp\Http\Requests\UpdateOtpWhitelistRequest;
use Modules\Otp\Models\OtpWhitelist;
use Mrj\Foundation\Http\Controllers\Controller;

class OtpWhitelistController extends Controller
{
    /**
     * Display a listing of the whitelist entries.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', OtpWhitelist::class);

        $whitelists = OtpWhitelist::query()
            ->orderByDesc('id')
            ->paginate(perPage());

        return view('otp::whitelist.index', compact('whitelists'));
    }

    /**
     * Show the form for creating a new whitelist entry.
     */
    public function create(): View
    {
        $this->authorize('create', OtpWhitelist::class);

        return view('otp::whitelist.create');
    }

    /**
     * Store a newly created whitelist entry in storage.
     */
    public function store(StoreOtpWhitelistRequest $request, StoreOtpWhitelistAction $action): RedirectResponse
    {
        $this->authorize('create', OtpWhitelist::class);

        $entry = $action->execute($request->validated());

        if ($entry === null) {
            return redirect()->back()->withInput()->with('error', __('otp::otp.flash.whitelist_duplicate'));
        }

        return redirect()->route('admin.otp-whitelist.index')->with('success', __('otp::otp.flash.whitelist_created'));
    }

    /**
     * Display the specified whitelist entry.
     */
    public function show(OtpWhitelist $otpWhitelist): View
    {
        $this->authorize('view', $otpWhitelist);

        return view('otp::whitelist.show', ['whitelist' => $otpWhitelist]);
    }

    /**
     * Show the form for editing the specified whitelist entry.
     */
    public function edit(OtpWhitelist $otpWhitelist): View
    {
        $this->authorize('update', $otpWhitelist);

        return view('otp::whitelist.edit', ['whitelist' => $otpWhitelist]);
    }

    /**
     * Update the specified whitelist entry in storage.
     */
    public function update(UpdateOtpWhitelistRequest $request, OtpWhitelist $otpWhitelist, UpdateOtpWhitelistAction $action): RedirectResponse
    {
        $this->authorize('update', $otpWhitelist);

        $updated = $action->execute($otpWhitelist, $request->validated());

        if (! $updated) {
            return redirect()->back()->withInput()->with('error', __('otp::otp.flash.whitelist_update_conflict'));
        }

        return redirect()->route('admin.otp-whitelist.index')->with('success', __('otp::otp.flash.whitelist_updated'));
    }

    /**
     * Remove the specified whitelist entry from storage.
     */
    public function destroy(OtpWhitelist $otpWhitelist): RedirectResponse
    {
        $this->authorize('delete', $otpWhitelist);

        $otpWhitelist->delete();

        return redirect()->route('admin.otp-whitelist.index')->with('success', __('otp::otp.flash.whitelist_deleted'));
    }
}
