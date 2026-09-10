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

            <v-razorpay-oauth-disconnect></v-razorpay-oauth-disconnect>
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

    @pushOnce('scripts')
        <script type="text/x-template" id="v-razorpay-oauth-disconnect-template">
            <button
                type="button"
                class="secondary-button w-max"
                :disabled="isDisconnecting"
                @click="disconnect"
            >
                @lang('razorpay::app.oauth.disconnect')
            </button>
        </script>

        <script type="module">
            app.component('v-razorpay-oauth-disconnect', {
                template: '#v-razorpay-oauth-disconnect-template',

                data() {
                    return {
                        isDisconnecting: false,
                    };
                },

                methods: {
                    disconnect() {
                        if (this.isDisconnecting) {
                            return;
                        }

                        this.isDisconnecting = true;

                        this.$axios.delete("{{ route('admin.razorpay.oauth.disconnect') }}")
                            .then(() => window.location.reload())
                            .catch(() => {
                                this.isDisconnecting = false;

                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: "@lang('razorpay::app.response.something-went-wrong')",
                                });
                            });
                    },
                },
            });
        </script>
    @endpushOnce
@endif
