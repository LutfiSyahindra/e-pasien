<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\epasien\Profile\PatientEmailOnboardingService;
use App\Services\epasien\Profile\PatientProfileService;
use App\Services\epasien\Profile\ProfilePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(
        Request $request,
        PatientProfileService $patientProfileService,
        PatientEmailOnboardingService $patientEmailOnboardingService
    ): View {
        $user = $request->user()->loadMissing('roles');
        $patient = $patientProfileService->findForUser($user);

        return view('e-pasien.profile.show', [
            'user' => $user,
            'patient' => $patient,
            'patientOverview' => $patientProfileService->overview($patient),
            'patientGroups' => $patientProfileService->detailGroups($patient),
            'patientCompletion' => $patientProfileService->completion($patient),
            'showEmailOnboarding' => $request->session()->get('patient_email_onboarding', false)
                && $patientEmailOnboardingService->shouldPrompt($user),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePhoto(Request $request, ProfilePhotoService $profilePhotoService): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'profile_photo_cropped' => ['nullable', 'string'],
        ], [
            'profile_photo.image' => 'File harus berupa gambar.',
            'profile_photo.mimes' => 'Foto profil harus berformat JPG, JPEG, PNG, atau WEBP.',
            'profile_photo.max' => 'Ukuran foto profil maksimal 2 MB.',
        ]);

        if ($validator->fails()) {
            $request->request->remove('profile_photo_cropped');

            throw new ValidationException($validator);
        }

        $user = $request->user();
        $oldPhotoPath = $user->profile_photo_path;
        $croppedPhoto = trim((string) $request->input('profile_photo_cropped', ''));

        if ($croppedPhoto !== '') {
            if (strlen($croppedPhoto) > 3145728) {
                $request->request->remove('profile_photo_cropped');

                throw ValidationException::withMessages([
                    'profile_photo_cropped' => 'Hasil crop foto profil maksimal 2 MB.',
                ]);
            }

            $photoPath = $profilePhotoService->storeDataUrl($croppedPhoto, $user->id);

            if (! $photoPath) {
                $request->request->remove('profile_photo_cropped');

                throw ValidationException::withMessages([
                    'profile_photo_cropped' => 'Hasil crop foto profil tidak valid atau terlalu besar.',
                ]);
            }
        } elseif ($request->hasFile('profile_photo')) {
            $photoPath = $profilePhotoService->store($request->file('profile_photo'), $user->id);
        } else {
            throw ValidationException::withMessages([
                'profile_photo' => 'Foto profil wajib dipilih.',
            ]);
        }

        $user->forceFill([
            'profile_photo_path' => $photoPath,
        ])->save();

        $profilePhotoService->delete($oldPhotoPath);

        return Redirect::route('profile.edit')->with('status', 'profile-photo-updated');
    }

    public function destroyPhoto(Request $request, ProfilePhotoService $profilePhotoService): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            $profilePhotoService->delete($user->profile_photo_path);

            $user->forceFill([
                'profile_photo_path' => null,
            ])->save();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-photo-deleted');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
