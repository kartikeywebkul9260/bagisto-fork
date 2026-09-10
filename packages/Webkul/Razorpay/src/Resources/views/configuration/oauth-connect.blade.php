@php
    $razorpayOAuth = app(\Webkul\Razorpay\Services\RazorpayOAuth::class);

    $hasCredentials = $razorpayOAuth->getClientId() && $razorpayOAuth->getClientSecret();
@endphp

@if ($razorpayOAuth->isEnabled())
    <div class="mb-4 flex flex-col gap-3 rounded border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
        <p class="font-semibold text-gray-800 dark:text-gray-300">
            @lang('razorpay::app.oauth.title')
        </p>

        @if ($razorpayOAuth->isConnected())
            <p class="text-sm text-green-700 dark:text-green-500">
                @lang('razorpay::app.oauth.connected-account', ['account' => $razorpayOAuth->getAccountId()])
            </p>

            <form
                action="{{ route('admin.razorpay.oauth.disconnect') }}"
                method="POST"
            >
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="secondary-button"
                >
                    @lang('razorpay::app.oauth.disconnect')
                </button>
            </form>
        @elseif (! $hasCredentials)
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @lang('razorpay::app.oauth.credentials-missing')
            </p>
        @else
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @lang('razorpay::app.oauth.connect-info')
            </p>

            <a
                href="{{ route('admin.razorpay.oauth.authorize') }}"
                class="primary-button w-max"
            >
                @lang('razorpay::app.oauth.connect')
            </a>
        @endif
    </div>
@endif
