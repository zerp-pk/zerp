<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Zerp\Hrm\Models\Employee;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthApiController extends Controller
{
    use ApiResponseTrait;
    public function login(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'email'    => 'required|string|email',
                    'password' => 'required|string',
                ]
            );
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->errorResponse('The provided credentials are incorrect.');
            }
            $user->tokens()->delete();
            $token = $user->createToken('api-token')->plainTextToken;

            $module_name = $request->module;
            if (!empty($module_name)) {
                if ($module_name == 'Hrm' && in_array($user->type, $user->not_emp_type)) {
                    return $this->errorResponse('Staff members are the only ones allowed to log in to this application.');
                }
                $module_status = Module_is_active($module_name,  $user->created_by);
                if ($module_status != true) {
                    return $this->errorResponse('Your Add-on Is Not Activated!');
                }
            }

            $data = ['user' => $this->getUserArray($user->id), 'token' => $token, 'type' => 'bearer'];

            return $this->successResponse($data, 'User retrieved successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('something went wrong');
        }
    }
    public function getUserArray($user_id = null)
    {
        $user = User::find($user_id);
        if (!$user) {
            return $this->errorResponse('User not found.',404);
        }
        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'mobile_no' => $user->mobile_no,
            'type'      => $user->type,
            'avatar'    => $user->avatar ? getImageUrlPrefix() . '/' . $user->avatar : getImageUrlPrefix() . '/' . 'avatar.png',
            'lang'      => $user->lang ?? 'en',
        ];
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if (!empty($user)) {
            $user->currentAccessToken()->delete();

            return $this->successResponse('Logged out successfully');
        } else {
            return $this->errorResponse('Invalid login details');
        }
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        $data = ['user' => $this->getUserArray($user->id), 'token' => $token];
        return $this->successResponse($data, 'Token refreshed successfully');
    }

    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password'         => 'required|confirmed|min:6',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('The provided current password does not match our records.');
            }
            if (Hash::check($request->password, $user->password)) {
                return $this->errorResponse('The provided password and old password are same.');
            }

            $user->password = Hash::make($request->password);
            $user->save();
            $data = $this->getUserArray($user->id);

            return $this->successResponse($data, 'Password changed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function editProfile(Request $request)
    {
        try {
            if ($request->user_id) {
                $user = User::find($request->user_id);
            } elseif ($request->user()) {
                $user = $request->user();
            }
            if ($user) {
                $validator = Validator::make($request->all(), [
                    'name'      => 'required|string',
                    'mobile_no' => 'required|string',
                    'email'     => [
                        'required',
                        Rule::unique('users')->where(function ($query) use ($user) {
                            return $query->whereNotIn('id', [$user->id])
                                ->where('created_by', creatorId());
                        }),
                    ],
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }
                // Handle profile image upload
                if ($request->hasFile('profile')) {
                    $filenameWithExt = $request->file('profile')->getClientOriginalName();
                    $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension       = $request->file('profile')->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                    $path = upload_file($request, 'profile', $fileNameToStore, '');

                    if ($path['flag'] == 0) {
                        return $this->errorResponse($path['msg']);
                    }

                    // Delete old avatar if exists
                    if ($user->avatar_media_id && $user->avatarMedia) {
                        \App\Services\MediaAttachmentService::deleteMedia($user->avatarMedia);
                    } elseif (!empty($user->avatar) && strpos($user->avatar, 'avatar.png') === false && getImageUrlPrefix($user->avatar)) {
                        delete_file($user->avatar);
                    }

                    $user->avatar = ltrim($path['url'], '/');
                }
                //  Update user fields
                $user->name      = $request->name;
                $user->email     = $request->email;
                $user->mobile_no = $request->mobile_no;
                $user->save();

                if ($request->hasFile('profile') && $user->avatar) {
                    $media = \App\Services\MediaAttachmentService::resolveOrBackfill(
                        $user->avatar,
                        User::class,
                        $user->id,
                        'avatars',
                        $user->id,
                        $user->created_by ?? $user->id,
                        \App\Services\MediaAttachmentService::ensureDirectory('User Avatars', $user->created_by ?? $user->id, $user->id)
                    );
                    $user->update(['avatar_media_id' => $media?->id]);
                }

                $data = $this->getUserArray($user->id);

                return $this->successResponse($data, 'Profile updated successfully');
            } else {
                return $this->errorResponse('User not found', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function deleteAccount(Request $request)
    {
        try {
            $user = $request->user();
            $user->delete();

            return $this->successResponse(null, 'Account deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
