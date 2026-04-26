<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    |
    | Don't forget to set this in your .env file, as it will be used to sign
    | your tokens. A helper command is provided for this:
    | `php artisan jwt:secret`
    |
    | Note: This will be used for Symmetric algorithms only (HMAC),
    | since RSA and ECDSA use a private/public key combo (See below).
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Keys
    |--------------------------------------------------------------------------
    |
    | The algorithm you are using, will determine whether your tokens are
    | signed with a random string (defined in `JWT_SECRET`) or using the
    | following public & private keys.
    |
    | Symmetric Algorithms:
    | HS256, HS384 & HS512 will use `JWT_SECRET`.
    |
    | Asymmetric Algorithms:
    | RS256, RS384 & RS512 / ES256, ES384 & ES512 will use the keys below.
    |
    */

    'keys' => [
        /*
        |--------------------------------------------------------------------------
        | Public Key
        |--------------------------------------------------------------------------
        |
        | A path or resource to your public key.
        |
        | E.g. 'file://path/to/public/key'
        |
        */

        'public' => env('JWT_PUBLIC_KEY'),

        /*
        |--------------------------------------------------------------------------
        | Private Key
        |--------------------------------------------------------------------------
        |
        | A path or resource to your private key.
        |
        | E.g. 'file://path/to/private/key'
        |
        */

        'private' => env('JWT_PRIVATE_KEY'),

        /*
        |--------------------------------------------------------------------------
        | Passphrase
        |--------------------------------------------------------------------------
        |
        | The passphrase for your private key. Can be null if none set.
        |
        */

        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT time to live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token will be valid for.
    | For security reasons, we use a short TTL (15 minutes) to minimize exposure
    | if a token is compromised. Users can refresh tokens using refresh_token.
    |
    | This aligns with our IAM security requirements:
    | - Short-lived access tokens reduce attack surface
    | - Refresh tokens provide seamless user experience
    | - Token rotation enhances security
    |
    */

    'ttl' => (int) env('JWT_TTL', 15),

    /*
    |--------------------------------------------------------------------------
    | Refresh time to live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token can be refreshed
    | within. For our IAM system, we use 30 days (43200 minutes) which provides
    | a good balance between security and user experience.
    |
    | This aligns with our security model:
    | - Users don't need to re-authenticate frequently
    | - Tokens can be revoked if compromised
    | - Supports "Remember Me" functionality
    | - Compatible with mobile applications
    |
    | Note: Our TokenService handles refresh token rotation for enhanced security
    |
    */

    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 43200), // 30 days

    /*
    |--------------------------------------------------------------------------
    | JWT hashing algorithm
    |--------------------------------------------------------------------------
    |
    | Specify the hashing algorithm that will be used to sign the token.
    | We use HS256 for symmetric signing which is secure and performant.
    | For higher security environments, consider RS256 with public/private keys.
    |
    | Available algorithms:
    | - HS256, HS384, HS512 (HMAC with SHA-256/384/512)
    | - RS256, RS384, RS512 (RSA with SHA-256/384/512)
    | - ES256, ES384, ES512 (ECDSA with SHA-256/384/512)
    |
    */

    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | Specify the required claims that must exist in any token.
    | A TokenInvalidException will be thrown if any of these claims are not
    | present in the payload.
    |
    */

    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    |
    | Specify the claim keys to be persisted when refreshing a token.
    | For our IAM system, we persist organization and role information
    | to maintain context across token refreshes.
    |
    | These claims are automatically included in refreshed tokens:
    | - org_id: Organization context for multi-tenancy
    | - roles: User roles for authorization
    | - permissions: User permissions cache
    |
    */

    'persistent_claims' => [
        'org_id',
        'roles', 
        'permissions',
        'device_name',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    |
    | This will determine whether a `prv` claim is automatically added to
    | the token. The purpose of this is to ensure that if you have multiple
    | authentication models e.g. `App\User` & `App\OtherPerson`, then we
    | should prevent one authentication request from impersonating another,
    | if 2 tokens happen to have the same id across the 2 different models.
    |
    | Under specific circumstances, you may want to disable this behaviour
    | e.g. if you only have one authentication model, then you would save
    | a little on token size.
    |
    */

    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway
    |--------------------------------------------------------------------------
    |
    | This property gives the jwt timestamp claims some "leeway".
    | For production environments with multiple servers, we allow 60 seconds
    | of clock skew tolerance to handle minor time synchronization issues.
    |
    | This applies to the claims `iat`, `nbf` and `exp`.
    |
    | Recommended: 60 seconds for distributed systems, 0 for single server
    |
    */

    'leeway' => (int) env('JWT_LEEWAY', 60),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    |
    | CRITICAL for IAM security: Blacklist must be enabled to support:
    | - Secure logout functionality
    | - Token revocation on password change
    | - Account lockout enforcement
    | - MFA requirement enforcement
    | - Emergency token invalidation
    |
    */

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    /*
    | -------------------------------------------------------------------------
    | Blacklist Grace Period
    | -------------------------------------------------------------------------
    |
    | Grace period to handle concurrent requests with the same token.
    | Set to 10 seconds to allow for reasonable request overlap while
    | maintaining security. This prevents legitimate concurrent requests
    | from failing due to token refresh timing.
    |
    */

    'blacklist_grace_period' => (int) env('JWT_BLACKLIST_GRACE_PERIOD', 10),

    /*
    |--------------------------------------------------------------------------
    | Show blacklisted token option
    |--------------------------------------------------------------------------
    |
    | For IAM security monitoring, we enable blacklist exception logging.
    | This helps track potential security incidents and token abuse attempts.
    | Set to false in production if logs become too verbose.
    |
    */

    'show_black_list_exception' => env('JWT_SHOW_BLACKLIST_EXCEPTION', true),

    /*
    |--------------------------------------------------------------------------
    | Cookies encryption
    |--------------------------------------------------------------------------
    |
    | For IAM API security, we disable cookie decryption since we primarily
    | use Authorization headers for token transmission. If you plan to use
    | cookies for token storage (e.g., for web frontend), consider enabling
    | this and adding the cookie name to EncryptCookies middleware exceptions.
    |
    | Our IAM system uses:
    | - Authorization: Bearer {token} for API requests
    | - Secure, HttpOnly cookies for web clients (if needed)
    |
    */

    'decrypt_cookies' => env('JWT_DECRYPT_COOKIES', false),

    /*
    |--------------------------------------------------------------------------
    | Cookie key name
    |--------------------------------------------------------------------------
    |
    | Secure cookie name for IAM token storage. Use a non-obvious name
    | to reduce fingerprinting and automated attacks.
    |
    */

    'cookie_key_name' => env('JWT_COOKIE_NAME', 'iam_session'),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Specify the various providers used throughout the package.
    |
    */

    'providers' => [
        /*
        |--------------------------------------------------------------------------
        | JWT Provider
        |--------------------------------------------------------------------------
        |
        | Specify the provider that is used to create and decode the tokens.
        |
        */

        'jwt' => PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci::class,

        /*
        |--------------------------------------------------------------------------
        | Authentication Provider
        |--------------------------------------------------------------------------
        |
        | Specify the provider that is used to authenticate users.
        |
        */

        'auth' => PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate::class,

        /*
        |--------------------------------------------------------------------------
        | Storage Provider
        |--------------------------------------------------------------------------
        |
        | Specify the provider that is used to store tokens in the blacklist.
        |
        */

        'storage' => PHPOpenSourceSaver\JWTAuth\Providers\Storage\Illuminate::class,
    ],
];
