<?php

namespace Database\Seeders;

use App\Modules\Finance\Models\PaymentGatewayRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentGatewayRoleSeeder extends Seeder
{
    /**
     * Map Spatie role slugs to PesaPal gateway role types.
     * Multiple Spatie roles can share the same gateway role.
     */
    private const ROLE_MAP = [
        'initiator'  => ['accountant'],
        'approver'   => ['procurement-officer'],
        'processor'  => ['principal'],
        'reconciler' => ['finance-manager'],
        'admin'      => ['super-admin'],
    ];

    public function run(): void
    {
        $provider = 'pesapal';

        foreach (self::ROLE_MAP as $gatewayRole => $spatieRoles) {
            foreach ($spatieRoles as $spatieRole) {
                // Find all users with this Spatie role
                $users = DB::table('users')
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('roles.name', $spatieRole)
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->select('users.id')
                    ->get();

                foreach ($users as $user) {
                    PaymentGatewayRole::updateOrCreate(
                        [
                            'gateway_provider' => $provider,
                            'user_id'          => $user->id,
                            'role_type'        => $gatewayRole,
                        ],
                        [
                            'is_active'    => true,
                            'activated_at' => now(),
                            'assigned_at'  => now(),
                            'permissions'  => null,
                        ]
                    );
                }
            }
        }

        $this->command->info('PaymentGatewayRoleSeeder: gateway roles assigned.');
    }
}
