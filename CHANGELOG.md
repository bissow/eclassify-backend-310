# Changelog: Test / Default OTP Service Provider (123456)

All notable changes related to the **Test / Default OTP Service Provider** implementation across Laravel Backend, Next.js Frontend Web, and Flutter Mobile App.

---

## [Feature] Test OTP Service Provider

### Overview
Introduced a native **Test / Default OTP** service provider into the **OTP Provider Settings** of the backend admin panel. When activated by an administrator, the platform bypasses external SMS gateways (such as Twilio or 2Factor) and allows any user to register, log in, or reset passwords across the website and mobile app using the default OTP: **`123456`**.

---

### 1. Laravel Backend (`eclassify-backend`)

#### Added / Modified Files:
- **`resources/views/settings/login-method.blade.php`**:
  - Added option `<option value="test">Test / Default OTP (123456)</option>` to the `otp_service_provider` dropdown.
  - Added a dedicated `#test-settings` configuration section explaining test mode and offering an optional configurable `test_otp_code` field (defaulting to `123456`).
  - Updated JavaScript `toggleOtpProviders()` function to show/hide the test settings panel accordingly.
- **`app/Services/HelperService.php`**:
  - Hardened `changeEnv()` to safely verify `.env` file existence, auto-initialize from `.env.example` if available, check write permissions, and catch exceptions without crashing when running in Docker/containerized VPS environments where `.env` may be missing or read-only.
- **`app/Http/Controllers/SettingController.php`**:
  - Expanded validation rules for `otp_service_provider` to accept `firebase,twilio,2factor,test,test_otp,default`.
  - Added validation for `test_otp_code` (`nullable|string|digits:6`).
  - Added check in `store()` so email environment settings are only synchronized to `.env` when email fields are actually present in the request, preventing unintended `.env` writes when saving OTP provider settings.
- **`app/Http/Controllers/Api/AuthApiController.php`**:
  - **`getOtp()`**: Added test provider handler. Generates the default test OTP (`123456` or custom configured value), stores a secure `bcrypt` hash in the `number_otps` table with a 2-hour expiration window, logs the issuance, and immediately returns a success response.
  - **`verifyOtp()`**: Added test provider handler. Verifies the input code against `123456` (or database hash). Upon valid verification, automatically creates a new user or retrieves existing user, issues a Sanctum Bearer token, and completes authentication.
  - **`userSignup()`**: When test OTP provider is active and `is_login` is true with phone authentication, entering `123456` in the password/credential field allows instantaneous login (and auto-registers first-time phone users).
- **`app/Http/Controllers/Api/SettingsApiController.php`**:
  - Added `test_otp_code` to the public settings whitelist query in `getSystemSettings()`.
- **`app/Services/DefaultSettingService.php` & `config/constants.php`**:
  - Registered `test_otp_code` with default value `123456`.
- **`resources/lang/en.json`**:
  - Added full translation key mappings for all admin panel test OTP labels, helper texts, and descriptions.

---

### 2. Next.js Frontend Web (`eclassify-frontend-web`)

#### Added / Modified Files:
- **`features/auth/register/RegisterWithMobileForm.jsx`**:
  - Updated submission logic to route mobile registration through the backend `/api/get-otp` service when `otp_service_provider === "test"`.
- **`features/auth/OtpScreen.jsx`**:
  - Updated OTP verification to invoke `verifyOtpApi.verifyOtp()` (`/api/verify-otp`) when `otp_service_provider === "test"`.
  - Updated OTP resend to invoke `getOtpApi.getOtp()` (`/api/get-otp`) when `otp_service_provider === "test"`.
- **`features/auth/login/LoginModal.jsx`**:
  - Updated forgot password flow to dispatch OTP via `/api/get-otp` when `otp_service_provider === "test"`.

---

### 3. Flutter Mobile App (`eclassify-mobile-app`)

#### Added / Modified Files:
- **`lib/core/enums/otp_provider_type.dart`**:
  - Added `test` entry to `OtpProviderType` enum.
  - Updated `OtpProviderType.fromRaw()` to case-insensitively parse `'test'`, `'test_otp'`, and `'default'`.
  - Added `bool get isFirebase => this == OtpProviderType.firebase;` helper.
- **`lib/core/models/system_settings.dart`**:
  - Hardened `otp_service_provider` JSON parsing with null-safety fallback.
- **Routing**:
  - In `lib/features/auth/cubits/base_otp_cubit.dart`, non-firebase providers automatically route to `ThirdPartyOtpService` which hits `/api/get-otp` and `/api/verify-otp`.

---

### 4. Upgrade Toolkit Patches

Modular git patches have been generated in `upgrade_toolkit/patches/`:
- `upgrade_toolkit/patches/backend/0008-feat-otp-Test-default-OTP-123456-provider.patch`
- `upgrade_toolkit/patches/frontend/0008-feat-otp-Test-default-OTP-123456-provider.patch`
- `upgrade_toolkit/patches/mobile-app/0008-feat-otp-Test-default-OTP-123456-provider.patch`
