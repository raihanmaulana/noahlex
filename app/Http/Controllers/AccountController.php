<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\UserNotificationPreference;
use App\Models\NotificationPreferenceMaster;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class AccountController extends Controller
{

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'company_name' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile_images', 'public');

            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $validated['profile_image'] = $path;
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user,
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed'
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.'
            ], 403);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'userUpdateId' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.'
        ]);
    }

    public function getNotificationSettings()
    {
        $userId = auth()->id();

        $masters = NotificationPreferenceMaster::all();
        $userPrefs = UserNotificationPreference::where('user_id', $userId)->get()->keyBy('key');

        $result = $masters->map(function ($item) use ($userPrefs) {
            return [
                'key' => $item->key,
                'label' => $item->label,
                'description' => $item->description, // default: true
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function updateNotificationSettings(Request $request)
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.key' => 'required|string|exists:notification_preference_masters,key',
            'preferences.*.enabled' => 'required|boolean'
        ]);

        $userId = auth()->id();

        foreach ($request->preferences as $pref) {
            UserNotificationPreference::updateOrCreate(
                ['user_id' => $userId, 'key' => $pref['key']],
                ['enabled' => $pref['enabled']]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferensi notifikasi berhasil diperbarui.'
        ]);
    }

    public function generate2FASecret()
    {
        $user = auth()->user();

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user->google2fa_secret = $secret;
        $user->save();

        $company = 'Noahlex App';
        $qrContent = $google2fa->getQRCodeUrl(
            $company,
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrCode = base64_encode($writer->writeString($qrContent));

        return response()->json([
            'secret' => $secret,
            'qr_code' => "data:image/svg+xml;base64,{$qrCode}"
        ]);
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = auth()->user();
        $google2fa = new Google2FA();

        $isValid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

        if ($isValid) {
            $user->is_2fa_enabled = true;
            $user->save();

            return response()->json(['message' => '2FA activated.']);
        } else {
            return response()->json(['message' => 'Invalid OTP.'], 422);
        }
    }

    public function show(Request $request)
    {
        // Ambil user dari JWT dan muat role + permissions
        $user = auth()->user()?->load([
            'role:id,name',
            'role.permissions:id,name,label'
        ]);

        if (!$user) {
            return response()->json([
                'code'    => 'UNAUTHORIZED',
                'message' => 'Invalid or expired token',
            ], 401);
        }

        // Bentuk response yang rapi & aman (tanpa field sensitif)
        $data = [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'company_name'   => $user->company_name,
            'profile_image'  => $user->profile_image,
            'is_2fa_enabled' => (bool) $user->is_2fa_enabled,
            'role' => $user->role ? [
                'id'          => $user->role->id,
                'name'        => $user->role->name,
                'label'       => $user->role->label,
                'permissions' => $user->role->permissions->map(function ($p) {
                    return [
                        'id'    => $p->id,
                        'name'  => $p->name,
                        'label' => $p->label,
                    ];
                })->values(),
            ] : null,
        ];

        // Sukses → kembalikan data apa adanya (tanpa code/message)
        return response()->json($data);
    }
}
