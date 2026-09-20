# Service auth

ATProto services authenticate inter-service requests with a short-lived JWT signed by the *user's* signing key, published in their DID document under the `#atproto` verification method. An AppView calling your feed generator sends one; verifying it means resolving the issuer's DID and checking the signature against the keys that document lists.

The resolution and key half is [karanshukla/php-atproto-identity](https://github.com/karanshukla/php-atproto-identity), a dependency of this library. `ServiceAuthVerifier` is the part that turns it into a token decision: audience, expiry, `lxm`, and which key to try.

```php
use Aazsamir\Libphpsky\ServiceAuth\ServiceAuthVerifier;
use KaranShukla\PhpAtprotoIdentity\Resolution\Cache\Psr6DidDocumentCache;
use KaranShukla\PhpAtprotoIdentity\Resolution\HttpDidDocumentResolver;

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

`verify()` throws `ServiceAuthException` for anything it cannot accept, including a resolution that failed. Callers that treat auth as optional catch it and fall back to an anonymous request; callers that require it turn it into a 401.

`ServiceAuthVerifier::unverifiedIssuer($jwt)` reads the issuer out of a token without verifying anything. It is for logs only, but it is what makes an account-specific auth failure visible rather than looking like a stale feed.

## Three behaviours worth knowing

**A missing `lxm` claim is tolerated; a wrong one is rejected.** The claim postdates the original service-auth spec, so an absent one means an older implementation, not a token for the wrong method. Rejecting those 401s a whole client, whose users then see a permanently empty feed.

**A signature that fails against every published key earns exactly one refetch.** That is what a key rotation looks like. Any other failure (expired, malformed, wrong audience) does not, because a fresher document cannot fix it.

**Every key the document publishes is tried, not just the first.** During a rotation a document lists more than one `#atproto` key, and the one that verifies a given signature is not reliably listed first.

## Resolution, caching and keys

All of it belongs to the package, and its README and `docs/` cover it: PSR-18 client and PSR-17 factory in, any PSR-6 pool or your own two-method cache, `did:plc` through a PLC directory and `did:web` through `/.well-known/did.json`, and `secp256k1` (`ES256K`) and P-256 (`ES256`) keys.

Two things are worth knowing here rather than there.

**Resolution has two freshness bounds rather than one TTL.** A document under `$staleAfter` (an hour by default) is served without a network call; an older one is refetched; a document under `$maxAge` (a day) is still served if that refetch fails. A rotation is therefore visible within the hour, without a directory outage taking auth down with it.

**A token's issuer is a DID you did not choose.** It decides which URL gets fetched, so the package refuses a URL bent to another host or path, refuses a host that is not a public domain, and checks the returned document claims the DID it was fetched for. If your callers come from a known set, pass `allowedHosts` and nothing else is fetched at all.
