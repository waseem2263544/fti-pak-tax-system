<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class CredentialApiController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!\Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = \Auth::user();
        $token = bin2hex(random_bytes(32));

        // Store token in session/cache
        \Cache::put('ext_token_' . $token, $user->id, now()->addHours(12));

        return response()->json([
            'token' => $token,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }

    public function searchClients(Request $request)
    {
        $user = $this->authenticate($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $q = $request->get('q', '');
        $clients = Client::where('name', 'like', "%{$q}%")
            ->orWhere('fbr_username', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'status', 'fbr_username', 'kpra_username']);

        return response()->json($clients->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'status' => $c->status,
            'has_fbr' => !empty($c->fbr_username),
            'has_kpra' => !empty($c->kpra_username),
            'has_secp' => !empty($c->getRawOriginal('secp_password')),
        ]));
    }

    public function getCredentials(Request $request, Client $client)
    {
        $user = $this->authenticate($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $portal = $request->get('portal', 'fbr');

        $data = ['client_name' => $client->name];

        switch ($portal) {
            case 'fbr':
                $data['username'] = $client->fbr_username;
                $data['password'] = $client->fbr_password;
                $data['pin'] = $client->it_pin_code;
                break;
            case 'kpra':
                $data['username'] = $client->kpra_username;
                $data['password'] = $client->kpra_password;
                $data['pin'] = $client->kpra_pin;
                break;
            case 'secp':
                $data['password'] = $client->secp_password;
                $data['pin'] = $client->secp_pin;
                break;
        }

        return response()->json($data);
    }

    private function authenticate(Request $request)
    {
        $token = $request->header('X-Extension-Token') ?? $request->get('token');
        if (!$token) return null;

        $userId = \Cache::get('ext_token_' . $token);
        if (!$userId) return null;

        return \App\Models\User::find($userId);
    }
}
