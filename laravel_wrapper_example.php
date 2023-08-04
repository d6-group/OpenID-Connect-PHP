<?php

use Jumbojett\OpenIDConnectClient;

/**
 * Sample for a basic wrapper class which uses Laravel built-in request and session functionality.
 * This solves the issue of using $_REQUEST + $_SESSION (which don't exist) within the context of a Laravel Octane application (for example).
 * 
 * One can, for example, use authenticateSession() and signOutSession() in place of authenticate() and signOut() directly to handle
 * storing and accessing SSO access tokens automatically.
 */
class OpenIDConnectClientLaravelWrapper extends OpenIDConnectClient
{
    /**
     * Our overridden Redirect URL, which we can use to manually redirect from the calling class
     *
     * @var string|null
     */
    public ?string $redirectUrl;

    /**
     * Override constructor to automatically pass through request.
     *
     * @param string|null $provider_url optional
     * @param string|null $client_id optional
     * @param string|null $client_secret optional
     * @param string|null $issuer
     * @param array|null $request
     */
    public function __construct(string $provider_url = null, string $client_id = null, string $client_secret = null, string $issuer = null, array $request = null)
    {
        parent::__construct(
            provider_url: $provider_url,
            client_id: $client_id,
            client_secret: $client_secret,
            issuer: $issuer,
            request: $request ?? request()->toArray()
        );
    }

    /**
     * Intercept redirect and just set the url locally, calling class can redirect on positive response to authenticateSession.
     * 
     * @param string $url
     * @return void
     */
    public function redirect($url)
    {
        $this->redirectUrl = $url;

        // Uncomment this to perform a laravel redirect here, if that is preferred
        // redirect($url);
    }

    /**
     * Overridden authenticate method.
     *
     * @return bool
     */
    public function authenticateSession() : bool
    {
        $authenticated = $this->authenticate();

        if ($authenticated) {
            $this->setSessionKey('sso_access_token', $this->getAccessToken());
        }

        return $authenticated;
    }

    /**
     * Overridden authenticate method.
     *
     * @param string|null $redirect
     * @return void
     */
    public function signOutSession(?string $redirect)
    {
        $idToken = $this->getSessionKey('sso_access_token');
        $this->unsetSessionKey('sso_access_token');
        $this->signOut($idToken, $redirect);
    }

    /**
     * Use session to manage a nonce
     * Override the default session handler to use laravel's request session.
     * @return void
     */
    protected function startSession()
    {
    }

    /**
     * @return void
     */
    protected function commitSession()
    {
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getSessionKey(string $key)
    {
        if (request()->session()->exists($key)) {
            return request()->session()->get($key);
        }

        return false;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setSessionKey(string $key, $value)
    {
        request()->session()->put([$key => $value]);
    }

    /**
     * @param string $key
     * @return void
     */
    protected function unsetSessionKey(string $key)
    {
        request()->session()->remove($key);
    }
}
