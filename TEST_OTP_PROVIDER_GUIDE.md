# Comprehensive Implementation & Porting Guide: Test / Default OTP Service Provider (123456)

This comprehensive guide describes how the **Test / Default OTP Service Provider** is designed, implemented, and verified in **Eclassify**, and provides exact step-by-step instructions so it can be easily replicated or ported to future versions (e.g. v3.2.0, v4.0.0, etc.).

---

## Table of Contents
1. [Architecture Overview](#1-architecture-overview)
2. [Backend Implementation (Laravel)](#2-backend-implementation-laravel)
3. [Frontend Web Implementation (Next.js)](#3-frontend-web-implementation-nextjs)
4. [Mobile App Implementation (Flutter)](#4-mobile-app-implementation-flutter)
5. [Automated Patch Application via Git](#5-automated-patch-application-via-git)
6. [Verification & Testing Playbook](#6-verification--testing-playbook)
7. [Troubleshooting & FAQs](#7-troubleshooting--faqs)

---

## 1. Architecture Overview

In standard Eclassify, OTP delivery is handled either by **Firebase Phone Authentication** (client-side SDK) or external SMS gateways like **Twilio** and **2Factor** (server-side API).

### How Test OTP Mode Works:
- **Admin Panel Control**: The administrator navigates to `Settings > OTP Provider Settings` (`/settings/login-method`) and selects **Test / Default OTP (123456)** (`otp_service_provider = "test"`).
- **Zero SMS Gateway Dependency**: No external credentials, API keys, or SMS credits are needed.
- **Unified Testing Code**: The fixed default test code is **`123456`** (with an optional configurable override via `test_otp_code`).
- **Registration Flow**:
  1. User enters mobile number on Web or Mobile app and clicks "Send OTP".
  2. Client calls `GET /api/get-otp?number={phone}&country_code={code}`.
  3. Backend generates `123456`, hashes it via `bcrypt`, saves it in the `number_otps` table (2-hour expiry), and responds with 200 Success.
  4. User enters `123456` on the OTP verification screen.
  5. Client calls `GET /api/verify-otp?number={phone}&country_code={code}&otp=123456&password={password}`.
  6. Backend verifies the code, creates the user account (assigning role `User` and a referral code), generates a Sanctum Bearer token, and logs the user in.
- **Login Flow**:
  - A user can either verify via OTP (`verify-otp`) or directly enter their phone and `123456` on the phone login screen (`POST /api/user-signup` with `is_login: 1`).
  - When test mode is active and the password matches `123456`, backend bypasses password check and authenticates the user immediately (auto-creating the user if first time).
- **Forgot Password Flow**:
  - User enters phone, receives test OTP `123456`, enters `123456`, and is immediately taken to the new password screen.

---

## 2. Backend Implementation (Laravel)

### Step 2.1: Admin Blade View
**File**: `resources/views/settings/login-method.blade.php`

1. Inside the `<select name="otp_service_provider" id="otp_service_provider">` dropdown, add:
```blade
<option value="test"
    {{ ($settings['otp_service_provider'] ?? '') == 'test' ? 'selected' : '' }}>
    {{ __('Test / Default OTP (123456)') }}
</option>
```

2. Below the 2Factor settings block (`#twofactor-settings`), add the `#test-settings` container:
```blade
{{-- ================= TEST OTP SETTINGS ================= --}}
<div class="col-12 mt-4 p-4 row bg-light d-none" id="test-settings">
    <h5>{{ __('Test OTP Settings') }}</h5>
    <p class="text-muted">
        {{ __('When this provider is selected, no external SMS gateway is needed. Users can register, log in, or reset passwords using the default test OTP (123456) on both the website and mobile app.') }}
    </p>

    <div class="form-group row mt-3">
        <div class="col-md-6">
            <label class="form-label">{{ __('Test Default OTP') }}</label>
            <input type="text" name="test_otp_code" class="form-control"
                placeholder="123456" value="{{ $settings['test_otp_code'] ?? '123456' }}"
                maxlength="6" pattern="[0-9]{6}">
            <small class="text-muted">{{ __('Default: 123456. Any 6-digit numeric OTP code.') }}</small>
        </div>
    </div>
</div>
```

3. In the `<script>` section, update `toggleOtpProviders()`:
```javascript
function toggleOtpProviders() {
    let provider = document.getElementById('otp_service_provider').value;

    document.getElementById('twilio-settings').classList.add('d-none');
    document.getElementById('twofactor-settings').classList.add('d-none');
    let testSettings = document.getElementById('test-settings');
    if (testSettings) {
        testSettings.classList.add('d-none');
    }

    if (provider === 'twilio') {
        document.getElementById('twilio-settings').classList.remove('d-none');
    }

    if (provider === '2factor') {
        document.getElementById('twofactor-settings').classList.remove('d-none');
    }

    if (provider === 'test' || provider === 'test_otp' || provider === 'default') {
        if (testSettings) {
            testSettings.classList.remove('d-none');
        }
    }
}
```

---

### Step 2.2: Settings Controller & Validation
**File**: `app/Http/Controllers/SettingController.php`

In `store(Request $request)` validation rules:
```php
'otp_service_provider' => 'nullable|in:firebase,twilio,2factor,test,test_otp,default',
'test_otp_code' => 'nullable|string|digits:6',
```

---

### Step 2.3: Default Settings & Constants
**File**: `app/Services/DefaultSettingService.php`
Add to default settings array:
```php
['name' => 'test_otp_code', 'value' => '123456', 'type' => 'string'],
```

**File**: `config/constants.php`
Add to default settings array:
```php
['name' => 'test_otp_code', 'value' => '123456', 'type' => 'string'],
```

---

### Step 2.4: Public Settings API
**File**: `app/Http/Controllers/Api/SettingsApiController.php`

In `getSystemSettings(Request $request)`:
Add `'test_otp_code'` to the `$query->whereIn('name', [...])` list right alongside `'otp_service_provider'`.

---

### Step 2.5: Authentication Controller
**File**: `app/Http/Controllers/Api/AuthApiController.php`

1. **In `getOtp(Request $request)`**:
```php
$provider = Setting::where('name', 'otp_service_provider')->value('value');

if (in_array($provider, ['test', 'test_otp', 'default'])) {
    $testOtp = Setting::where('name', 'test_otp_code')->value('value') ?: '123456';
    $expireAt = now()->addHours(2);

    $otpRecord = NumberOtp::updateOrCreate(
        ['number' => $number],
        [
            'otp' => bcrypt($testOtp),
            'expire_at' => $expireAt,
            'attempts' => 0,
        ]
    );

    \Log::info("Test OTP issued", [
        'number' => $number,
        'OTP' => $testOtp,
        'expire' => $expireAt,
    ]);

    DB::commit();
    return ResponseService::successResponse(__('OTP sent successfully.'));
}
```

2. **In `verifyOtp(Request $request)`**:
```php
$isTestMode = in_array($provider, ['test', 'test_otp', 'default']);
$testOtp = Setting::where('name', 'test_otp_code')->value('value') ?: '123456';

// Allow stateless verification in test mode even if get-otp wasn't called beforehand
if (! $otpRecord && ! ($isTestMode && (string) $request->otp === (string) $testOtp)) {
    DB::rollBack();
    return ResponseService::errorResponse(__('OTP not found.'));
}

if ($isTestMode) {
    if ((string) $request->otp !== (string) $testOtp && (! $otpRecord || ! Hash::check($request->otp, $otpRecord->otp))) {
        DB::rollBack();
        return ResponseService::validationError(__('Invalid OTP.'));
    }

    if ($otpRecord) {
        $otpRecord->delete();
    }
} elseif ($provider === 'twilio') {
    // twilio verification logic...
} elseif ($provider === '2factor') {
    // 2factor verification logic...
}
```

3. **In `userSignup(Request $request)`**:
Allow login using `123456` when test provider is active:
```php
if (in_array($type, ['phone', 'email', 'google']) && ! empty($request->password) && $request->boolean('is_login')) {

    $otpProvider = Setting::where('name', 'otp_service_provider')->value('value');
    $isTestOtpMode = in_array($otpProvider, ['test', 'test_otp', 'default']);
    $testOtpCode = Setting::where('name', 'test_otp_code')->value('value') ?: '123456';
    $isTestPassword = ($isTestOtpMode && $type == 'phone' && (string) $request->password === (string) $testOtpCode);

    if ($type == 'phone') {
        $mobile = ltrim($request->mobile, '+');
        $countryCode = ltrim($request->country_code, '+');
        $user = User::where('mobile', $mobile)->withTrashed()->first();
    } else {
        $user = User::where('email', $request->email)->withTrashed()->first();
    }

    if (!$user) {
        if ($isTestPassword) {
            $user = User::createWithReferralCode([
                'name' => 'user_' . (strlen($mobile) >= 4 ? substr($mobile, -4) : rand(1000, 9999)),
                'mobile' => $mobile,
                'type' => 'phone',
                'country_code' => $countryCode,
                'password' => Hash::make($testOtpCode),
            ]);
            $user->assignRole('User');
        } else {
            return ResponseService::errorResponse(
                __('User not found. Please signup first.')
            );
        }
    }

    if ($user->deleted_at) {
        return ResponseService::errorResponse(
            __('User is deactivated. Please contact the administrator.')
        );
    }

    if (! $isTestPassword) {
        if (in_array($type, ['phone', 'email']) && empty($user->password)) {
            return ResponseService::errorResponse(
                __('Password is not set. Please set your password using the forgot password option.')
            );
        }

        if (! Hash::check($request->password, $user->password)) {
            return ResponseService::errorResponse(__('Invalid password.'));
        }
    }
}
```

---

### Step 2.6: Backend Localization
**File**: `resources/lang/en.json`
Add the following keys:
```json
"Test / Default OTP (123456)": "Test / Default OTP (123456)",
"Test OTP Settings": "Test OTP Settings",
"When this provider is selected, no external SMS gateway is needed. Users can register, log in, or reset passwords using the default test OTP (123456) on both the website and mobile app.": "When this provider is selected, no external SMS gateway is needed. Users can register, log in, or reset passwords using the default test OTP (123456) on both the website and mobile app.",
"Test Default OTP": "Test Default OTP",
"Default: 123456. Any 6-digit numeric OTP code.": "Default: 123456. Any 6-digit numeric OTP code.",
"Test OTP Code": "Test OTP Code"
```

---

## 3. Frontend Web Implementation (Next.js)

### Step 3.1: Mobile Registration
**File**: `features/auth/register/RegisterWithMobileForm.jsx`

In `handleMobileSubmit()`:
```javascript
if (otp_service_provider === "twilio" || otp_service_provider === "2factor" || otp_service_provider === "test") {
  await sendOtpWithTwillio(PhoneNumber);
} else {
  await sendOtpWithFirebase(PhoneNumber);
}
```

### Step 3.2: OTP Screen Verification & Resend
**File**: `features/auth/OtpScreen.jsx`

In `verifyOTP()`:
```javascript
if (otp_service_provider === "twilio" || otp_service_provider === "2factor" || otp_service_provider === "test") {
  await verifyOTPWithTwillio();
} else {
  await verifyOTPWithFirebase();
}
```

In `resendOtp()`:
```javascript
if (otp_service_provider === "twilio" || otp_service_provider === "2factor" || otp_service_provider === "test") {
  await resendOtpWithTwillio(formattedNumber);
} else {
  await resendOtpWithFirebase(PhoneNumber);
}
```

### Step 3.3: Login Modal (Forgot Password)
**File**: `features/auth/login/LoginModal.jsx`

In `handleForgotPassword()`:
```javascript
if (otp_service_provider === "twilio" || otp_service_provider === "2factor" || otp_service_provider === "test") {
  try {
    const response = await getOtpApi.getOtp({ number: formattedNumber, country_code: countryCode });
    if (response?.data?.error === false) {
      toast.success(t("otpSentSuccess"));
      setResendTimer(60);
      setIsOTPScreen("otp");
    } else {
      toast.error(t("failedToSendOtp"));
    }
  } catch (error) {
    console.log(error);
  }
} else {
  // Firebase signInWithPhoneNumber...
}
```

---

## 4. Mobile App Implementation (Flutter)

### Step 4.1: OTP Provider Enum
**File**: `lib/core/enums/otp_provider_type.dart`

```dart
enum OtpProviderType {
  firebase,
  twilio,
  test;

  static OtpProviderType fromRaw(String raw) {
    final lower = raw.trim().toLowerCase();
    if (lower == 'firebase') {
      return OtpProviderType.firebase;
    } else if (lower == 'test' || lower == 'test_otp' || lower == 'default') {
      return OtpProviderType.test;
    }
    return OtpProviderType.twilio;
  }

  bool get isFirebase => this == OtpProviderType.firebase;
}
```

### Step 4.2: System Settings Model
**File**: `lib/core/models/system_settings.dart`

Update `SystemSettings.fromJson`:
```dart
otpProvider = OtpProviderType.fromRaw(
  (json['otp_service_provider'] as String?) ?? '',
),
```

### Step 4.3: Service Routing
In `lib/features/auth/cubits/base_otp_cubit.dart`, `_provider == OtpProviderType.firebase` automatically evaluates to `false` for `OtpProviderType.test`, correctly directing `sendOtp()` and `verifyOtp()` to `_thirdPartyOtpService` (which calls `/api/get-otp` and `/api/verify-otp`).

---

## 5. Automated Patch Application via Git

For convenience, modular patch files are available in `upgrade_toolkit/patches/`:

```powershell
# 1. Apply Backend Patch
cd "Eclassify Version 3.1.0/eclassify-backend"
git apply "../upgrade_toolkit/patches/backend/0008-feat-otp-Test-default-OTP-123456-provider.patch"

# 2. Apply Frontend Web Patch
cd "../eclassify-frontend-web"
git apply "../upgrade_toolkit/patches/frontend/0008-feat-otp-Test-default-OTP-123456-provider.patch"

# 3. Apply Mobile App Patch
cd "../eclassify-mobile-app"
git apply "../upgrade_toolkit/patches/mobile-app/0008-feat-otp-Test-default-OTP-123456-provider.patch"
```

---

## 6. Verification & Testing Playbook

### Test 1: Admin Panel Configuration
1. Open Admin Panel > Settings > OTP Provider Settings (`/settings/login-method`).
2. Select **Test / Default OTP (123456)** in the dropdown.
3. Observe that the **Test OTP Settings** panel appears.
4. Click **Save Settings**.
5. Check database:
   ```bash
   php artisan tinker --execute="echo App\Models\Setting::where('name', 'otp_service_provider')->value('value');"
   # Output: test
   ```

### Test 2: API Endpoints (cURL / Postman)

#### Send OTP:
```bash
curl -X GET "http://127.0.0.1:8000/api/get-otp?number=9999988888&country_code=+1"
```
**Expected Response:**
```json
{
  "error": false,
  "message": "OTP sent successfully.",
  "data": null,
  "code": 200
}
```

#### Verify Wrong OTP:
```bash
curl -X GET "http://127.0.0.1:8000/api/verify-otp?number=9999988888&country_code=+1&otp=999999"
```
**Expected Response:**
```json
{
  "error": true,
  "message": "Invalid OTP.",
  "data": null,
  "code": 102
}
```

#### Verify Correct Test OTP (123456):
```bash
curl -X GET "http://127.0.0.1:8000/api/verify-otp?number=9999988888&country_code=+1&otp=123456"
```
**Expected Response:**
```json
{
  "error": false,
  "message": "User logged-in successfully",
  "data": {
    "name": "user_8888",
    "mobile": "9999988888",
    "type": "phone"
  },
  "token": "1|KqxjmgOWY4U6B6o4CIB5MI2oEYLZRh...",
  "code": 200
}
```

#### Direct Login via Password Field with 123456:
```bash
curl -X POST "http://127.0.0.1:8000/api/user-signup" \
  -d "type=phone&mobile=9999988888&country_code=+1&password=123456&is_login=1"
```
**Expected Response:**
```json
{
  "error": false,
  "message": "User logged-in successfully",
  "data": { ... },
  "token": "2|sEL4MRJGopwTzAQWy...",
  "code": 200
}
```

---

## 7. Troubleshooting & FAQs

- **Q: Does Test OTP send real SMS messages?**  
  **A**: No. It completely bypasses SMS gateways and external HTTP calls. No SMS credits are consumed.
- **Q: Can I change the test OTP code from 123456 to something else?**  
  **A**: Yes. In the admin panel, when "Test / Default OTP (123456)" is selected, enter any 6-digit number in the **Test Default OTP** input and click **Save Settings**.
- **Q: What happens if a user didn't request an OTP first and enters 123456?**  
  **A**: `verifyOtp` includes a test-mode fallback: if the code entered is `123456`, it verifies successfully even without a pre-existing database record.
- **Q: When returning to production, how do I switch back?**  
  **A**: Select **Firebase**, **Twilio**, or **2Factor** in the admin panel and click **Save Settings**. Standard live SMS authentication resumes instantly.
