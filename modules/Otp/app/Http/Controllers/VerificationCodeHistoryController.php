<?php

namespace Modules\Otp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Queries\VerificationCodeQuery;

class VerificationCodeHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['can:View Verification Code History']);
    }

    /**
     * Display a paginated list of all sent verification codes.
     */
    public function index(Request $request): View
    {
        $contactType = $request->input('contact_type')
            ? ContactType::tryFrom($request->input('contact_type'))
            : null;

        $isVerified = $request->filled('is_verified')
            ? filter_var($request->input('is_verified'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $codes = VerificationCodeQuery::make()
            ->filterByContactType($contactType)
            ->filterByVerified($isVerified)
            ->filterByDateRange($request->input('date_from'), $request->input('date_to'))
            ->search($request->input('search'))
            ->orderByLatest()
            ->paginate(perPage());

        return view('otp::history.index', [
            'codes' => $codes,
            'contactTypes' => ContactType::options(),
        ]);
    }
}
