<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class PaymentReceiptService
{
    public static function generatePDF(PaymentTransaction $payment)
    {
        $settings = self::getSettings();
        $settings['company_logo'] = self::getLogoBase64($settings['company_logo'] ?? '');

        $html = view('payments.receipts.payment_receipt', [
            'payment' => $payment,
            'settings' => $settings,
        ])->render();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => 'dejavusans',
            'autoArabic' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf;
    }

    public static function generateHTML(PaymentTransaction $payment)
    {
        $settings = self::getSettings();
        $settings['company_logo'] = self::getLogoUrl($settings['company_logo'] ?? '');

        return view('payments.receipts.payment_receipt', [
            'payment' => $payment,
            'settings' => $settings,
        ])->render();
    }

    /**
     * Convert logo to base64 for PDF embedding
     */
    private static function getLogoBase64(string $companyLogo): string
    {
        if (empty($companyLogo)) {
            return '';
        }

        // If it's already a full URL from the Setting model accessor, try to resolve the local path
        if (str_contains($companyLogo, 'http')) {
            $companyLogo = str_replace(asset(''), '', $companyLogo);
            $companyLogo = str_replace(url('/storage/'), '', $companyLogo);
        }

        // Try public path first (for default logos like assets/images/logo/...)
        $logoPath = public_path($companyLogo);
        if (!file_exists($logoPath)) {
            // Try storage path
            $logoPath = storage_path('app/public/' . $companyLogo);
        }

        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath);
            $imageData = file_get_contents($logoPath);
            return 'data:' . $mime . ';base64,' . base64_encode($imageData);
        }

        return '';
    }

    /**
     * Get logo as a full URL for HTML rendering
     */
    private static function getLogoUrl(string $companyLogo): string
    {
        if (empty($companyLogo)) {
            return '';
        }

        if (str_contains($companyLogo, 'http')) {
            return $companyLogo;
        }

        if (str_contains($companyLogo, 'assets')) {
            return asset($companyLogo);
        }

        return url(\Storage::url($companyLogo));
    }

    public static function streamPDF(PaymentTransaction $payment)
    {
        $mpdf = self::generatePDF($payment);
        return response($mpdf->Output(self::getFileName($payment), 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . self::getFileName($payment) . '"',
        ]);
    }

    public static function downloadPDF(PaymentTransaction $payment)
    {
        $mpdf = self::generatePDF($payment);
        return response($mpdf->Output(self::getFileName($payment), 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . self::getFileName($payment) . '"',
        ]);
    }

    public static function getHtmlOutput(PaymentTransaction $payment)
    {
        $html = self::generateHTML($payment);
        return response($html)->header('Content-Type', 'text/html');
    }

    private static function getFileName(PaymentTransaction $payment): string
    {
        return 'payment_receipt_' . $payment->id . '.pdf';
    }

    private static function getSettings(): array
    {
        return CachingService::getSystemSettings([
            'company_name',
            'company_email',
            'company_tel1',
            'company_tel2',
            'company_address',
            'company_logo',
            'currency_symbol',
        ]);
    }
}
