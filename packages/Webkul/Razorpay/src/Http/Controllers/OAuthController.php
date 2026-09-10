<?php

namespace Webkul\Razorpay\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Webkul\Razorpay\Services\RazorpayOAuth;

class OAuthController extends Controller
{
    /**
     * Session key holding the anti-forgery state token.
     */
    public const STATE_SESSION_KEY = 'razorpay_oauth_state';

    /**
     * Create a new controller instance.
     */
    public function __construct(protected RazorpayOAuth $razorpayOAuth) {}

    /**
     * Begin the OAuth flow by redirecting to Razorpay for authorization.
     */
    public function authorize(): RedirectResponse
    {
        if (
            ! $this->razorpayOAuth->isEnabled()
            || ! $this->razorpayOAuth->getClientId()
            || ! $this->razorpayOAuth->getClientSecret()
        ) {
            session()->flash('error', trans('razorpay::app.oauth.credentials-missing'));

            return $this->backToConfiguration();
        }

        $state = Str::random(40);

        session()->put(self::STATE_SESSION_KEY, $state);

        return redirect()->away($this->razorpayOAuth->getAuthorizationUrl($state));
    }

    /**
     * Handle the authorization callback and store the issued tokens.
     */
    public function callback(Request $request): RedirectResponse
    {
        $expectedState = session()->pull(self::STATE_SESSION_KEY);

        if (
            ! $request->filled('state')
            || ! $expectedState
            || ! hash_equals($expectedState, $request->input('state'))
        ) {
            session()->flash('error', trans('razorpay::app.oauth.invalid-state'));

            return $this->backToConfiguration();
        }

        if (
            $request->has('error')
            || ! $request->filled('code')
        ) {
            session()->flash('error', $request->input('error_description', trans('razorpay::app.oauth.authorization-failed')));

            return $this->backToConfiguration();
        }

        try {
            $this->razorpayOAuth->exchangeCodeForToken($request->input('code'));

            session()->flash('success', trans('razorpay::app.oauth.connected'));
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', trans('razorpay::app.oauth.token-error'));
        }

        return $this->backToConfiguration();
    }

    /**
     * Disconnect the currently linked Razorpay account.
     */
    public function disconnect(Request $request): JsonResponse|RedirectResponse
    {
        $this->razorpayOAuth->clearTokens();

        session()->flash('success', trans('razorpay::app.oauth.disconnected'));

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => trans('razorpay::app.oauth.disconnected')]);
        }

        return $this->backToConfiguration();
    }

    /**
     * Redirect back to the Razorpay payment configuration page.
     */
    protected function backToConfiguration(): RedirectResponse
    {
        return redirect()->route('admin.configuration.index', [
            'slug' => 'sales',
            'slug2' => 'payment_methods',
        ]);
    }
}
