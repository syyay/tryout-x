<?php

namespace App\Http\Controllers;

use DateTime;
use Throwable;
use Faker\Factory;
use App\Models\User;
use App\Models\TryoutPKG;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function createUser(Request $request): Response
    {
        try {
            $validateUser = Validator::make($request->all(), [
                'username' => 'required',
                'full_name' => 'required',
                'password' => 'required',
                'email' => 'required|email|unique:users,email',
                'birth_date' => 'required|date',
                'phone_num' => 'required|starts_with:+',
                'school' => 'required',
                'major' => 'required|in:Saintek,Soshum',
                'grad_date' => 'required',
            ]);

            if ($validateUser->fails()) {
                return response([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validateUser->errors()
                ], 400);
            }

            $salt = Str::random(10);

            $user = User::forceCreate([
                'username' => $request->username,
                'full_name' => $request->full_name,
                'salt' => $salt,
                'password' => Hash::make($request->password . $salt),
                'email' => $request->email,
                'birth_date' => $request->birth_date,
                'phone_num' => $request->phone_num,
                'school' => $request->school,
                'major' => $request->major,
                'grad_date' => $request->grad_date,
            ]);
            
            Artisan::call('db:seed', ['--class' => 'UserPKGSeederForNewUser']);

            return response([
                'status' => true,
                'message' => 'Berhasil melakukan registrasi, silahkan verifikasi email anda.',
                'verification_token' => $token,
            ], 201);
        } catch (Throwable $th) {
            return response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function loginUser(Request $request): Response
    {
        try {
            $validateUser = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validateUser->fails()) {
                return Response([
                    'message' => 'Validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return Response([
                    'status' => false,
                    'message' => 'Account not found'
                ], 401);
            }

            if (Auth::attempt(['email' => $request->email, 'password' => $request->password . $user->salt])) {
                // Cek role pengguna
                if ($user->role === 'admin') {
                    // Logika untuk pengguna admin di sini
                    $login_token = $user->tokens()->where('name', 'Login Token')->get();
                    if ($login_token->first() != null) {
                        foreach ($login_token as $lt) {
                            $lt->delete();
                        }
                    }

                    // attach TO
                    $attach_to = TryoutPKG::whereDoesntHave('users', function ($query) use ($user) {
                        $query->where('users.user_id', $user->user_id);
                    })->pluck('tryout_id');
                    if (!empty($attach_to)) {
                        $user->tryouts()->attach($attach_to, ['shuffle_seed' => rand(1, 100)]);
                    }

                    $request->session()->regenerate();
                    $expiry = new DateTime();
                    $expiry->modify('+1 hour');
                    $success =  $user->createToken('Login Token', ['*'], $expiry)->plainTextToken;
                    $tkn = explode("|", $success);

                    return Response([
                        'status' => true,
                        'message' => 'Admin logged in successfully',
                        'token' => $tkn[1],
                        'userData' => $user->setVisible(['email', 'subscribed_at'])
                    ], 200);
                } else {
                    // Logika untuk pengguna biasa di sini
                    $login_token = $user->tokens()->where('name', 'Login Token')->get();
                    if ($login_token->first() != null) {
                        foreach ($login_token as $lt) {
                            $lt->delete();
                        }
                    }

                    $request->session()->regenerate();
                    $expiry = new DateTime();
                    $expiry->modify('+1 hour');
                    $success =  $user->createToken('Login Token', ['*'], $expiry)->plainTextToken;
                    $tkn = explode("|", $success);

                    return Response([
                        'status' => true,
                        'message' => 'User logged in successfully',
                        'token' => $tkn[1],
                        'userData' => $user->setVisible(['email', 'subscribed_at'])
                    ], 201);
                }
            }

            return Response([
                'status' => false,
                'message' => 'Email or password wrong'
            ], 401);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function getUser(Request $request)
    {

        try {
            $user = $request->user();
            return Response([
                'status' => true,
                'message' => $user
            ], 200);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getuserimg(Request $request)
    {
        //     try {
        //         $user = $request->user();

        //         if ($user) {
        //             $filename = $user->image;

        //             if (Storage::disk('public')->exists('images/' . $filename)) {
        //                 $file = Storage::disk('public')->path('images/' . $filename);

        //                 return response()->json([
        //                     'status' => true,
        //                     'image_url' => Storage::disk('public')->url('images/' . $filename)
        //                 ]);
        //             } else {
        //                 return response()->json([
        //                     'status' => false,
        //                     'message' => 'Photo not found'
        //                 ], 404);
        //             }
        //         } else {
        //             return response()->json([
        //                 'status' => false,
        //                 'message' => 'User not authenticated'
        //             ], 401);
        //         }
        //     } catch (\Throwable $e) {
        //         return response()->json([
        //             'status' => false,
        //             'message' => $e->getMessage()
        //         ], 500);
        //     }
        // }
        try {
            $user = $request->user();

            if ($user) {
                $filename = $user->image;

                if (Storage::disk('public')->exists('images/' . $filename)) {

                    $url = str_replace(':8000', '', url('/')) . '/projek_pkl/tryoutx-backend/storage/app/public/images/' . $filename;

                    return response()->json([
                        'status' => true,
                        'image_url' => $url
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Photo not found'
                    ], 404);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function createproimg(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:png,jpg|max:3000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation_error',
                    'errors' => $validator->errors()
                ], 401);
            }

            $data = [];

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = $file->hashName();
                $file->storeAs('images', $filename, 'public');
                $data['image'] = $filename;
            }

            $user = $request->user();
            $user->image = $filename;
            $user->save();
            $user->update($data);

            return response()->json([
                'status' => true,
                'message' => 'created profile image successfully'
            ], 200);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateimg(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:png,jpg|max:3000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation_error',
                    'errors' => $validator->errors()
                ], 401);
            }

            if ($request->hasFile('image')) {
                $user = $request->user();
                if ($user->image) {
                    Storage::disk('public')->delete('images/' . $user->image);
                }

                $file = $request->file('image');
                $filename = $file->hashName();
                $file->storeAs('images', $filename, 'public');

                $user->image = $filename;
                $user->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'updated profile image successfully'
            ], 200);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function deleteimg(Request $request)
    {
        try {
            $user = $request->user();

            $filename = $user->image;
            if ($filename && Storage::disk('public')->exists('images/' . $filename)) {
                Storage::disk('public')->delete('images/' . $filename);
            }

            $user->image = null;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'deleted profile image successfully'
            ], 200);;
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateUser(Request $request): Response
    {
        try {
            $time = new DateTime('Asia/Jakarta');
            $timenow = $time->format('Y');
            $validateUser = Validator::make($request->all(), [
                'username' => 'required',
                'full_name' => 'required',
                'email' => 'required|email',
                'birth_date' => 'required|date',
                'phone_num' => 'required|starts_with:+',
                // 'prov' => 'required',
                // 'city' => 'required',
                'school' => 'required',
                'major' => 'required|in:Saintek,Soshum',
                'grad_date' => 'required|gte:' . $timenow,
            ]);
            if ($validateUser->fails()) {
                return Response([
                    'status' => false,
                    'message' => 'validation_error',
                    'errors' => $validateUser->errors()
                ], 401);
            }
            $user = $request->user();
            $user->update($request->all());
            return Response([
                'status' => true,
                'message' => 'profile updated'
            ], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function logout(Request $request): Response
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return Response(['data' => 'User Logout successfully.'], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function forgotPass(Request $request): Response
    {
        try {
            $validateUser = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);
            if ($validateUser->fails()) {
                return Response([
                    'status' => false,
                    'message' => 'validation_error',
                    'errors' => $validateUser->errors()
                ], 401);
            }
            $status = Password::sendResetLink(
                $request->only('email')
            );
            return Response(['message' => $status], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function resetPass(Request $request): Response
    {
        try {
            $validateUser = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required|email|exists:users,email',
                'password' => 'required',
            ]);
            if ($validateUser->fails()) {
                return Response([
                    'status' => false,
                    'message' => 'validation_error',
                    'errors' => $validateUser->errors()
                ], 401);
            }
            $salt = Str::random(10);
            $status = Password::reset(
                $request->only('email', 'password', 'token'),
                function (User $user, string $password) use ($salt) {
                    $user->forceFill([
                        'salt' => $salt,
                        'password' => Hash::make($password . $salt)
                    ]);

                    $user->save();

                    event(new PasswordReset($user));
                }
            );
            return Response(['message' => $status], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 400);
    }
}
