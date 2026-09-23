<?php

namespace App\Http\Controllers;

use dacoto\EnvSet\Facades\EnvSet;
use dacoto\LaravelWizardInstaller\Controllers\InstallFolderController;
use dacoto\LaravelWizardInstaller\Controllers\InstallServerController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InstallerController extends Controller {
    public function purchaseCodeIndex() {
        if (!(new InstallServerController())->check() || !(new InstallFolderController())->check()) {
            return redirect()->route('LaravelWizardInstaller::install.folders');
        }
        return view('vendor.installer.steps.purchase-code');
    }


    public function checkPurchaseCode(Request $request) {
        try {
            $app_url = (string)url('/');
            $app_url = preg_replace('#^https?://#i', '', $app_url);

        //     $curl = curl_init();
        //    curl_setopt_array($curl, array(
        //         CURLOPT_URL            => 'https://validator.wrteam.in/eclassify_validator?purchase_code=' . $request->input('purchase_code') . '&domain_url=' . $app_url,
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_MAXREDIRS      => 10,
        //         CURLOPT_FOLLOWLOCATION => true,
        //         CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST  => 'GET',
        //         CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
        //     ));
        //     $response = curl_exec($curl);
        //     $curlError = curl_error($curl);
        //     $curlErrno = curl_errno($curl);
        //     $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //     curl_close($curl);

        //     if ($response === false || $curlError) {
        //         return view('vendor.installer.steps.purchase-code', [
        //             'error' => $this->curlErrorMessage($curlErrno, $curlError),
        //         ]);
        //     }

        //     if (in_array($httpCode, [403, 429, 451], true)) {
        //         return view('vendor.installer.steps.purchase-code', [
        //             'error' => 'The validation server rejected the request from your server\'s IP address '
        //                 . '(HTTP ' . $httpCode . '). Your hosting provider\'s IP may be blocked by our firewall/WAF, or you are being rate-limited. '
        //                 . 'Please <a href="https://www.wrteam.in/contact-us" target="_blank" class="underline">contact support</a> '
        //                 . 'with your server IP so we can whitelist it.',
        //         ]);
        //     }

        //     $response = json_decode($response, true);
        //     if (!is_array($response) || !array_key_exists('error', $response)) {
        //         return view('vendor.installer.steps.purchase-code', [
        //             'error' => 'Received an unexpected response from the validation server. Please try again or '
        //                 . '<a href="https://www.wrteam.in/contact-us" target="_blank" class="underline">contact support</a>.',
        //         ]);
        //     }

        //     if (!empty($response['error'])) {
        //         $message = $response['message'] ?? 'Validation failed.';
        //         $message .= ' If this purchase code is already registered to another domain, you can reset it here: '
        //             . '<a href="https://www.wrteam.in/reset-purchase-code" target="_blank" class="underline">Reset purchase code</a>.';
        //         return view('installer::steps.purchase-code', ['error' => $message]);
        //     }

        //     EnvSet::setKey('APPSECRET', $request->input('purchase_code'));
        //     EnvSet::save();

        $this->setEnvValue('APPSECRET', $request->input('purchase_code'));
        
            return redirect()->route('install.php-function.index');
        } catch (Exception $e) {
            $values = [
                'purchase_code' => $request->get("purchase_code"),
            ];
            return view('vendor.installer.steps.purchase-code', ['values' => $values, 'error' => $e->getMessage()]);
        }
    }

    private function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            throw new Exception('.env file does not exist.');
        }

        $env = file_get_contents($envPath);

        $escapedValue = '"' . addcslashes($value, '"\\') . '"';

        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

        if (preg_match($pattern, $env)) {
            $env = preg_replace(
                $pattern,
                $key . '=' . $escapedValue,
                $env
            );
        } else {
            $env = rtrim($env) . PHP_EOL . $key . '=' . $escapedValue . PHP_EOL;
        }

        if (file_put_contents($envPath, $env) === false) {
            throw new Exception('Unable to write to .env file.');
        }
    }


    private function curlErrorMessage(int $errno, string $curlError): string
    {
        $helpLink = '<a href="https://www.wrteam.in/knowledge-base/curl-connection-issues" target="_blank" class="underline">View troubleshooting guide</a>';

        $known = [
            CURLE_COULDNT_CONNECT   => 'Your server could not connect to the validation server on port 443. '
                . 'This usually means outbound HTTPS (port 443) connections are blocked by your hosting firewall. '
                . 'Please ask your hosting provider to allow outbound connections on port 443.',
            CURLE_COULDNT_RESOLVE_HOST => 'Your server could not resolve the validation server\'s domain name. '
                . 'This usually means DNS lookups are blocked or misconfigured on your server.',
            CURLE_OPERATION_TIMEOUTED => 'The connection to the validation server timed out. '
                . 'This usually means outbound connections on port 443 are blocked or very slow on your server.',
            CURLE_SSL_CONNECT_ERROR => 'Your server failed to establish an SSL connection. '
                . 'Please make sure the OpenSSL/cURL SSL certificates on your server are up to date.',
        ];

        $message = $known[$errno] ?? 'Connection error: ' . $curlError;

        return $message . ' ' . $helpLink . ' If the issue persists, '
            . '<a href="https://www.wrteam.in/contact-us" target="_blank" class="underline">contact support</a> '
            . 'with this error detail: <code>' . e($curlError) . '</code>.';
    }

    public function phpFunctionIndex() {
        if (!(new InstallServerController())->check() || !(new InstallFolderController())->check()) {
            return redirect()->route('LaravelWizardInstaller::install.purchase_code');
        }
        return view('vendor.installer.steps.symlink_basedir_check', [
            'result' => $this->checkSymlink(),
            'baseDir' =>$this->checkBaseDir(),
            'procOpen' => $this->checkProcOpen()
        ]);
    }

    public function checkSymlink(): bool
    {
        return function_exists('symlink');
    }
    public function checkProcOpen(): bool
    {
        return function_exists('proc_open');
    }
    public function checkBaseDir(): bool
    {
        $openBaseDir = ini_get('open_basedir');
        if ($openBaseDir) {
            return false;
        }
        return true;
    }

}
