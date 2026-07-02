<?php

namespace App\Services\Auth;

use App\Models\Auth\ApiUser;
use App\Models\Privilege\Role;
use App\Models\Workflow\WorkflowStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthService
{
    private const REFRESH_TOKEN_COOKIE = 'refresh_token';
    private const REFRESH_TOKEN_DAYS = 30;

    protected array $ldapServers;
    protected int $sanctumExpirationMinutes;

    protected int $page;
    protected int $perPage;

    protected bool $ldapActive;

    public function __construct()
    {
        $this->ldapServers = config('app.ldap_servers');
        $this->sanctumExpirationMinutes = config('sanctum.sanctum_expiration_minutes');
        $this->page = config('app.default_page');
        $this->perPage = config('app.default_per_page');
        $this->ldapActive = config('app.ldap_active');
    }

    public function getAllUsers(array $data): LengthAwarePaginator
    {
        $page = $data['page'] ?? $this->page;
        $perPage = $data['per_page'] ?? $this->perPage;
        $search = isset($data['search']) ? trim((string)$data['search']) : null;

        $paginator = ApiUser::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('employee_id', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email_address', 'like', "%{$search}%")
                        ->orWhere('mobile_no', 'like', "%{$search}%")
                        ->orWhere('orbit_branch_name', 'like', "%{$search}%")
                        ->orWhere('desig_name', 'like', "%{$search}%")
                        ->orWhere('division_name', 'like', "%{$search}%");
                });
            })
            ->paginate(perPage: $perPage, page: $page);

        $employeeIds = collect($paginator->items())->pluck('employee_id')->all();

        $roles = Role::join('user_roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('user_roles.employee_id', $employeeIds)
            ->where('user_roles.is_active', true)
            ->orderBy('roles.id')
            ->select('roles.*', 'user_roles.employee_id')
            ->get()
            ->groupBy('employee_id');

        $paginator->getCollection()->transform(function ($user) use ($roles) {
            $user->roles = $roles->get($user->employee_id, collect());
            return $user;
        });

        return $paginator;
    }


    public function getLoginUser(): ApiUser
    {
        return auth()->user();
    }

    public function getUserByEmail(string $email): ApiUser
    {
        return ApiUser::query()
            ->where('email_address', $email)
            ->firstOrFail();
    }

    public function getAUserByEmployeeId(): Model
    {
        $employeeId = auth()->user()->employee_id;

        $user = ApiUser::query()
            ->where('employee_id', $employeeId)
            ->firstOrFail();

        $roles = Role::join('user_roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.employee_id', $employeeId)
            ->where('user_roles.is_active', true)
            ->orderBy('roles.id')
            ->select('roles.*', 'user_roles.employee_id')
            ->get();

        $user->roles = $roles;
        return $user;
    }


    public function login(array $data): array
    {
        $data['email'] = strtolower($data['email']) . '@ificbankbd.com';

        Log::info('Login request for email: ' . $data['email']);

        if ($this->ldapActive) {
            $ldapAuthenticated = $this->authenticateViaLdap($data['email'], $data['password']);
            abort_if(!$ldapAuthenticated, 401, 'Your email or password is incorrect');
        }

        $apiUser = ApiUser::query()
            ->where('email_address', $data['email'])
            ->first();

        abort_if($apiUser === null, 401, 'Your email or password is invalid.');

        abort_if($apiUser['emp_status'] !== 'Active', 401, 'Your account is inactive.');

        $tokenPair = DB::connection('mysql')->transaction(function () use ($apiUser) {
            $apiUser->tokens()->delete();
            return $this->issueTokenPair($apiUser);
        });

        $permissions = DB::connection('mysql')
            ->table('user_roles as ur')
            ->join('role_permissions as rp', 'ur.role_id', '=', 'rp.role_id')
            ->join('permissions as p', 'rp.permission_id', '=', 'p.id')
            ->where('ur.employee_id', $apiUser->employee_id)
            ->where('ur.is_active', true)
            ->where('rp.is_active', true)
            ->where('p.is_active', true)
            ->distinct()
            ->pluck('p.permission_name');

        return [
            'data' => [
                'apiUser' => $apiUser,
                'access_token' => $tokenPair['access_token'],
                'token_type' => 'Bearer',
                'expires_in' => $tokenPair['expires_in'],
                'expires_at' => $tokenPair['expires_at'],
                'permissions' => $permissions,
            ],
            'refresh_token' => $tokenPair['refresh_token'],
        ];
    }

    private function authenticateViaLdap(string $email, string $password): bool
    {
        foreach ($this->ldapServers as $server) {
            $ldapCon = @ldap_connect($server);

            if (!$ldapCon) {
                Log::warning("LDAP connection failed for server: {$server}");
                continue;
            }

            ldap_set_option($ldapCon, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapCon, LDAP_OPT_REFERRALS, 0);

            $bind = @ldap_bind($ldapCon, $email, $password);

            @ldap_unbind($ldapCon);

            if ($bind) {
                Log::info("LDAP authentication successful via server: {$server}");
                return true;
            }
        }

        Log::warning("LDAP authentication failed for email: {$email}");
        return false;
    }

    public function refresh(Request $request): array
    {
        $refreshToken = $request->cookie(self::REFRESH_TOKEN_COOKIE)
            ?? $request->input('refresh_token')
            ?? $request->bearerToken();

        abort_if($refreshToken === null, 401, "Refresh token missing.");

        return DB::connection('mysql')->transaction(function () use ($refreshToken) {
            $tokenHash = $this->hashRefreshToken($refreshToken);

            $tokenRow = DB::connection('mysql')
                ->table('refresh_tokens')
                ->where('token', $tokenHash)
                ->lockForUpdate()
                ->first();

            abort_if(
                !$tokenRow || Carbon::parse($tokenRow->expires_at)->isPast(),
                400,
                'Refresh token is invalid.'
            );

            $apiUser = ApiUser::query()->find($tokenRow->api_user_id);

            abort_if(!$apiUser, 400, 'User not found.');

            abort_if($apiUser['emp_status'] !== 'Active', 401, 'Your account is inactive.');

            DB::connection('mysql')
                ->table('refresh_tokens')
                ->where('id', $tokenRow->id)
                ->delete();

            $apiUser->tokens()->delete();

            $tokenPair = $this->issueTokenPair($apiUser);

            return [
                'data' => [
                    'apiUser' => $apiUser,
                    'access_token' => $tokenPair['access_token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $tokenPair['expires_in'],
                    'expires_at' => $tokenPair['expires_at'],
                ],
                'refresh_token' => $tokenPair['refresh_token'],
            ];
        });
    }

    private function issueTokenPair(ApiUser $apiUser): array
    {
        $expiresAt = now()->addMinutes($this->sanctumExpirationMinutes);
        $refreshToken = Str::random(64);

        DB::connection('mysql')->table('refresh_tokens')->insert([
            'api_user_id' => $apiUser->employee_id,
            'token' => $this->hashRefreshToken($refreshToken),
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_DAYS),
        ]);

        return [
            'access_token' => $apiUser->createToken('api-token', ['*'], $expiresAt)->plainTextToken,
            'refresh_token' => $refreshToken,
            'expires_in' => now()->diffInSeconds($expiresAt),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    private function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();
            DB::connection('mysql')
                ->table('refresh_tokens')
                ->where('api_user_id', $user->employee_id)
                ->delete();
        }
    }
}
