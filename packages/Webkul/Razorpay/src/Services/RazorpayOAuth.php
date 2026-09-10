<?php

namespace Webkul\Razorpay\Services;

use Illuminate\Support\Facades\Http;
use Webkul\Core\Repositories\CoreConfigRepository;

class RazorpayOAuth
{
    /**
     * Razorpay OAuth authorization server base url.
     */
    public const AUTH_BASE_URL = 'https://auth.razorpay.com';

    /**
     * Razorpay API base url.
     */
    public const API_BASE_URL = 'https://api.razorpay.com/v1';

    /**
     * Scope requested while connecting a merchant account.
     */
    public const SCOPE = 'read_write';

    /**
     * Number of seconds before expiry at which a token is treated as stale.
     */
    public const EXPIRY_LEEWAY = 120;

    /**
     * Config path prefix for every Razorpay setting.
     */
    protected string $configPrefix = 'sales.payment_methods.razorpay.';

    /**
     * Create a new service instance.
     */
    public function __construct(protected CoreConfigRepository $coreConfigRepository) {}

    /**
     * Determine whether the OAuth flow is enabled in configuration.
     */
    public function isEnabled(): bool
    {
        return (bool) core()->getConfigData($this->configPrefix.'oauth');
    }

    /**
     * Determine whether a merchant account is currently connected.
     */
    public function isConnected(): bool
    {
        return $this->isEnabled()
            && ! empty($this->getConfig('access_token'))
            && ! empty($this->getConfig('refresh_token'));
    }

    /**
     * Determine whether the connected account runs in sandbox mode.
     */
    public function isSandbox(): bool
    {
        return (bool) core()->getConfigData($this->configPrefix.'sandbox');
    }

    /**
     * Get the OAuth application client id for the active mode.
     */
    public function getClientId(): ?string
    {
        return $this->isSandbox()
            ? core()->getConfigData($this->configPrefix.'test_client_id')
            : core()->getConfigData($this->configPrefix.'client_id');
    }

    /**
     * Get the OAuth application client secret for the active mode.
     */
    public function getClientSecret(): ?string
    {
        return $this->isSandbox()
            ? core()->getConfigData($this->configPrefix.'test_client_secret')
            : core()->getConfigData($this->configPrefix.'client_secret');
    }

    /**
     * Get the connected account mode, either test or live.
     */
    public function getMode(): string
    {
        return $this->isSandbox() ? 'test' : 'live';
    }

    /**
     * Get the redirect uri registered with the OAuth application.
     */
    public function getRedirectUri(): string
    {
        return route('admin.razorpay.oauth.callback');
    }

    /**
     * Build the authorization url the admin is redirected to.
     */
    public function getAuthorizationUrl(string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'scope' => self::SCOPE,
            'state' => $state,
        ]);

        return self::AUTH_BASE_URL.'/authorize?'.$query;
    }

    /**
     * Exchange an authorization code for access and refresh tokens.
     *
     * @throws \Exception
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post(self::AUTH_BASE_URL.'/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'redirect_uri' => $this->getRedirectUri(),
            'mode' => $this->getMode(),
            'code' => $code,
        ]);

        if ($response->failed()) {
            throw new \Exception($response->json('error_description') ?? trans('razorpay::app.oauth.token-error'));
        }

        $token = $response->json();

        $this->storeTokens($token);

        return $token;
    }

    /**
     * Refresh the stored access token using the refresh token.
     */
    public function refreshToken(): ?string
    {
        $refreshToken = $this->getConfig('refresh_token');

        if (empty($refreshToken)) {
            return null;
        }

        $response = Http::asForm()->post(self::AUTH_BASE_URL.'/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'refresh_token' => $refreshToken,
            'mode' => $this->getMode(),
        ]);

        if ($response->failed()) {
            return null;
        }

        $token = $response->json();

        $this->storeTokens($token);

        return $token['access_token'] ?? null;
    }

    /**
     * Get a non-expired access token, refreshing it when required.
     */
    public function getValidAccessToken(): ?string
    {
        $accessToken = $this->getConfig('access_token');

        if (empty($accessToken)) {
            return null;
        }

        $expiresAt = (int) $this->getConfig('token_expires_at');

        if (
            $expiresAt > 0
            && $expiresAt - self::EXPIRY_LEEWAY <= time()
        ) {
            return $this->refreshToken();
        }

        return $accessToken;
    }

    /**
     * Get the public token used as the checkout key in OAuth mode.
     */
    public function getPublicToken(): ?string
    {
        return $this->getConfig('public_token');
    }

    /**
     * Create a Razorpay order on the connected account.
     *
     * @throws \Exception
     */
    public function createOrder(array $payload): array
    {
        $accessToken = $this->getValidAccessToken();

        if (empty($accessToken)) {
            throw new \Exception(trans('razorpay::app.oauth.not-connected'));
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post(self::API_BASE_URL.'/orders', $payload);

        if ($response->failed()) {
            throw new \Exception($response->json('error.description') ?? trans('razorpay::app.response.something-went-wrong'));
        }

        return $response->json();
    }

    /**
     * Fetch a payment from the connected account for verification.
     */
    public function fetchPayment(string $paymentId): ?array
    {
        $accessToken = $this->getValidAccessToken();

        if (empty($accessToken)) {
            return null;
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get(self::API_BASE_URL.'/payments/'.$paymentId);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Persist the token payload returned by the authorization server.
     */
    public function storeTokens(array $token): void
    {
        $values = [
            'access_token' => $token['access_token'] ?? null,
            'refresh_token' => $token['refresh_token'] ?? null,
            'public_token' => $token['public_token'] ?? null,
            'razorpay_account_id' => $token['razorpay_account_id'] ?? null,
            'token_expires_at' => isset($token['expires_in']) ? time() + (int) $token['expires_in'] : null,
        ];

        foreach ($values as $code => $value) {
            if (is_null($value)) {
                continue;
            }

            $this->saveConfig($code, (string) $value);
        }
    }

    /**
     * Remove every stored OAuth token, disconnecting the account.
     */
    public function clearTokens(): void
    {
        foreach (['access_token', 'refresh_token', 'public_token', 'razorpay_account_id', 'token_expires_at'] as $code) {
            $this->coreConfigRepository->deleteWhere(['code' => $this->configPrefix.$code]);
        }
    }

    /**
     * Get the connected Razorpay account id.
     */
    public function getAccountId(): ?string
    {
        return $this->getConfig('razorpay_account_id');
    }

    /**
     * Read a stored Razorpay config value by its short code.
     */
    protected function getConfig(string $code): mixed
    {
        return core()->getConfigData($this->configPrefix.$code);
    }

    /**
     * Persist a Razorpay config value by its short code.
     */
    protected function saveConfig(string $code, string $value): void
    {
        $this->coreConfigRepository->updateOrCreate(
            [
                'code' => $this->configPrefix.$code,
                'channel_code' => null,
                'locale_code' => null,
            ],
            ['value' => $value]
        );
    }
}
