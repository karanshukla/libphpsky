# Service auth

ATProto services authenticate inter-service requests with a short-lived JWT signed by the *user's* signing key, published in their DID document under the `#atproto` verification method. An AppView calling your feed generator sends one; verifying it means resolving the issuer's DID and checking the signature against the keys that document lists.

The DID resolution and key decoding underneath this live in [`karanshukla/php-atproto-identity`](https://github.com/karanshukla/php-atproto-identity), which libphpsky depends on. `ServiceAuthVerifier` is the ATProto-specific half: the claim checks and the rotation handling. Import `HttpDidDocumentResolver`, `Psr6DidDocumentCache` and `DidDocumentCache` from `KaranShukla\PhpAtprotoIdentity`.

```php
$verifier = new ServiceAuthVerifier(
    new HttpDidDocumentResolver(
        httpClient: new \GuzzleHttp\Client(['timeout' => 5, 'connect_timeout' => 3]),
        requestFactory: new \GuzzleHttp\Psr7\HttpFactory(),
        cache: new Psr6DidDocumentCache($pool),
    ),
);

$token = $verifier->verify(
    jwt: $bearerToken,
    audience: 'did:web:feed.example.com',
    lxm: 'app.bsky.feed.getFeedSkeleton',
);

$token->issuer; // the DID that made the request
```

`verify()` throws `ServiceAuthException` for anything it cannot accept, including failures that originate in the identity layer — a DID that will not resolve is wrapped rather than surfaced as its own exception type, so there is only one thing to catch. Callers that treat auth as optional catch it and fall back to an anonymous request; callers that require it turn it into a 401.

`ServiceAuthVerifier::unverifiedIssuer($jwt)` reads the issuer out of a token without verifying anything. It is for logs only, but it is what makes an account-specific auth failure visible rather than looking like a stale feed.

## Three behaviours worth knowing

**A missing `lxm` claim is tolerated; a wrong one is rejected.** The claim postdates the original service-auth spec, so an absent one means an older implementation, not a token for the wrong method. Rejecting those 401s a whole client, whose users then see a permanently empty feed.

**A signature that fails against every published key earns exactly one refetch.** That is what a key rotation looks like. Any other failure — expired, malformed, wrong audience — does not, because a fresher document cannot fix it.

**Resolution has two freshness bounds rather than one TTL.** A document under `$staleAfter` (an hour by default) is served without a network call; an older one is refetched; a document under `$maxAge` (a day) is still served if that refetch fails. A rotation is therefore visible within the hour, without a directory outage taking auth down with it.

## Caching DID documents

`HttpDidDocumentResolver` defaults to `NullDidDocumentCache`, which resolves on every request. Give it something real in production.

`Psr6DidDocumentCache` wraps any PSR-6 pool:

```php
$cache = new Psr6DidDocumentCache(
    new \Symfony\Component\Cache\Adapter\FilesystemAdapter(),
);
```

`DidDocumentCache` is deliberately not PSR-6 or PSR-16 itself. Both answer "is this still valid"; the resolver needs "how old is this", so that a document too stale to serve blindly can still be served when the directory is unreachable. Implementing the two-method interface against a database table or APCu is a few lines.

## Supported DID methods

`did:plc` resolves through a PLC directory (`https://plc.directory` by default, override with the `plcDirectory` argument) and `did:web` through the domain's `/.well-known/did.json`. A `did:web` with a path is rejected.

Signing keys on both curves ATProto uses are supported: `secp256k1` (`ES256K`) and NIST P-256 (`ES256`).

Both of the above are the identity package's remit, so that is where to add a DID method or a curve.
