<?php

namespace Tests\Unit;

use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use Tests\TestCase;

/**
 * authorize() is now the single permission gate for these actions (it used to be
 * a scattered `if (can()) {} else { back() }` in each controller, and authorize()
 * just returned true). These assert the gate actually checks the capability, so a
 * revert to `return true;` fails the build. See zerp-pk/zerp#46.
 */
class FormRequestAuthorizeTest extends TestCase
{
    private function requestWithUser(string $class, ?bool $canReturn)
    {
        $req = $class::create('/', 'POST');

        $req->setUserResolver(function () use ($canReturn) {
            if ($canReturn === null) {
                return null; // unauthenticated
            }

            return new class($canReturn)
            {
                public function __construct(private bool $can) {}

                public function can($ability): bool
                {
                    return $this->can;
                }
            };
        });

        return $req;
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function capabilityRequests(): array
    {
        return [
            'StoreCouponRequest'     => [StoreCouponRequest::class],
            'StoreUserRequest'       => [StoreUserRequest::class],
            'UpdateWarehouseRequest' => [UpdateWarehouseRequest::class],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('capabilityRequests')]
    public function test_allows_when_the_user_holds_the_permission(string $class): void
    {
        $this->assertTrue($this->requestWithUser($class, true)->authorize());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('capabilityRequests')]
    public function test_denies_when_the_user_lacks_the_permission(string $class): void
    {
        $this->assertFalse($this->requestWithUser($class, false)->authorize());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('capabilityRequests')]
    public function test_denies_when_unauthenticated(string $class): void
    {
        $this->assertFalse($this->requestWithUser($class, null)->authorize());
    }
}
