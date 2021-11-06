<?php

namespace Motor\Admin\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Motor\Admin\Traits\ChangesPasswords;

/**
 * Class ChangePasswordController
 *
 * @package Motor\Admin\Http\Controllers\Auth
 */
class ChangePasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ChangesPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/backend';

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showChangeForm(Request $request)
    {
        return view('motor-admin::auth.passwords.change')->with([
            'email' => Auth::guard()
                           ->user()->email,
        ]);
    }

    /**
     * @param Request $request
     */
    public function saveNewPassword(Request $request)
    {
        // Fixme: do something here
    }
}
