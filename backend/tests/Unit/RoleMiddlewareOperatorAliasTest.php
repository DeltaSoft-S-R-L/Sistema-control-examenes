<?php

namespace Tests\Unit;

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Http\Request;
use Tests\TestCase;

class RoleMiddlewareOperatorAliasTest extends TestCase
{
    public function test_operador_can_access_control_ingreso_routes(): void
    {
        $request = $this->requestWithRole('OPERADOR');
        $response = (new RoleMiddleware())->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            'CONTROL_INGRESO',
            'ADMINISTRADOR',
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_operador_does_not_gain_administrator_access(): void
    {
        $request = $this->requestWithRole('OPERADOR');
        $response = (new RoleMiddleware())->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            'ADMINISTRADOR',
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    private function requestWithRole(string $role): Request
    {
        $user = new class($role) {
            public object $rol;

            public function __construct(string $role)
            {
                $this->rol = (object) ['nombre' => $role];
            }

            public function loadMissing(string $relation): self
            {
                return $this;
            }
        };

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
