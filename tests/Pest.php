<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @return array{0: string, 1: string} [credentialsPath, projectId]
 */
function fakeFirebaseServiceAccount(): array
{
    $projectId = 'test-fcm-project';
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fcm-sa-'.uniqid('', true).'.json';

    $privateKey = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCyTkTZh8eXgdNc
4CGvzEWVe4VN6MIhsSLpsiyPN1hvxViHssBHpET1/U0FzF1d5arNCfVxeMMTkzQ1
PsKHgSp29fzA9Aeps/pKcmkNEM/PoOzCMoL6pbn9AmbuaSWQT9/d7uYCEfrQI8Qg
FgtrE/7iKe7Aq+ptv3buHU7ik/zM7YA6ryGEWkvSNVGzap8axEWKJom9a66YPK+J
xHUe/T5HZB/L8aZuVhLhG+rOqSQlCB3i1cIt//GHYhAslN0n6+0De7KYPsb9KrJ0
RDzXtGTcFXZN0Pl+JD/0KAm8RHraekG98cUyzw//V+I9ufJASqnhwHHd/TPJvU8P
5UWVEeGVAgMBAAECggEABTXKRzzzdRbjETSvZ06lvd91Hrita4Sci42cRbcNS37N
vnFXswA2z6VOKdw14jeCzSj8Vgt8T8ceYGvsDT+V+RHpB85ZpvjOWHyNW0dheySt
pmMJdr9+9siqL089PMFIvlhruiXCWXCiG3npbYCRExS8mD6tw/UzSXPryS0f1vER
COJyKmRTIpETPAZmfwUn37vaPwAwPzfV5orn8xY/JNbiFiNaqqG9319Y1R5CmOqo
QROVChAss0fqTwQ4ZwtN3Xj+2Zys0uK9+bi5eK4CPHcnRTJseX4avuZF2AnrW6W3
lFwYtnwKwAhugx+aIxD+RWRXuyLP/uLeTLwTOjKS4QKBgQD6KR3ZUBzAreT+oZjU
0/iluTJOE07oAnxVqw6UU3yi5pIC3dHAJ8U29jS3q0t3F1OIbt55ZrX5RIJP7xon
Jajb2/Ysv5wPsfn/aaaShgf0hjP0xEpDAzgGhSUf2Pfm3e1UWfBRXMTvitQFtL/s
dwI6hZm/m7YxO5uyU74yL3QLXQKBgQC2d8UCk+QKSZUycB0d56kmDBPrEv6H1YGj
eldu5jZiBHF4ogA+PWvDCZ7ZxtiROWKdPfsDC03gzlrtKo897XJnAfnIVqCE0iID
UnFkFQ08RR5dzCP3U606gKeku6CcVSvYIAFPITOJYpPUktqSmN9zL2tkZG1b5Qrc
jdlHOpkDmQKBgQDTNQvCgmUEOM1yPrVuW1k/clEKojqooBChs76ndKVyVAFK8kU5
W1qiwNRZjgI1FjispA6pqRJS1oi/PDx0eNNMbTY3Kb91cOqFvomohAkLZpNPQLsC
QSF4P8nMTb9f6FeMNDM2PoD3TdscxjKUTxZOmqXopGr6vj0CHroiXPY48QKBgB6q
RjvGqv5nm2FcoigSdMTmJrrM+GXPpffZabRDkEQsxv0lDLFpFSE0DrQ+zMqrQD14
2ySJ087CH1nYWzZnH2DcXiQjGtr3javNQS02tIC6hP3yfuDj+Srp0ELJHZboYXPy
/QVVaRnfrUR+Yaldc8Ah6gR3NEHLXmXumON1n52RAoGBAPOyamqhagJAI/mQSBiH
nAJh5gLT5CNXnEaoDA13sqOnG+E07CskKlFtEkaHRI8EhFE2UEMyVVOeUHgo3arQ
+zUQw1B3IP37u0Ozh39uY7nvQKBsuS2p61gxfLQJ1qzfnSHmezSmFpJ900zlP/FZ
JIHbO33draeY/vr9c8EeA+BM
-----END PRIVATE KEY-----
PEM;

    file_put_contents($path, json_encode([
        'type' => 'service_account',
        'project_id' => $projectId,
        'client_email' => 'firebase-adminsdk@'.$projectId.'.iam.gserviceaccount.com',
        'private_key' => $privateKey,
    ], JSON_THROW_ON_ERROR));

    config([
        'push.firebase_credentials' => $path,
        'push.firebase_project_id' => $projectId,
    ]);

    Cache::flush();

    return [$path, $projectId];
}
