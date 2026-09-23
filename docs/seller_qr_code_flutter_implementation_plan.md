# Flutter Mobile App Implementation Guide: Seller QR Code & Digital Catalog

**Date:** 2026-09-10  
**Target Platform:** Flutter (Android & iOS)  
**Backend API:** Eclassify Laravel 12 API (`/api/seller-qr/*`)

---

## 1. Overview & Architecture

The Seller QR Code feature allows buyers to scan physical QR codes (posters, banners, standees) and digital QR codes to immediately explore a seller's complete digital catalog with real-time location mismatch alerts, sorting, filtering, and ad detail navigation.

For Sellers, the mobile app allows subscribed merchants to customize, preview, download, and share their branded UPI/Google Pay style standees.

```mermaid
flowchart TD
    Scanner[In-App QR Scanner / External Camera] -->|Scans QR Code| DeepLinkHandler[Deep Link & Universal URL Router]
    DeepLinkHandler -->|Token or Slug| QRStoreScreen[SellerStoreQRScreen]
    QRStoreScreen -->|1. Fetch Catalog & GPS Check| API[/api/seller-qr/store/:identifier]
    API -->|Location Mismatch?| WarningBanner[Show Sleek Location Notice Banner]
    API -->|Categories & Items| CatalogView[Interactive Store Catalog]
    CatalogView -->|Tap Item| AdDetails[Ad Details Screen]
```

---

## 2. Recommended Flutter Dependencies

In `pubspec.yaml`:
```yaml
dependencies:
  # QR Scanner
  mobile_scanner: ^5.2.3
  
  # Deep Linking
  app_links: ^6.3.2

  # Location (device coordinates for mismatch check)
  geolocator: ^13.0.2

  # File Downloading & Sharing
  dio: ^5.7.0
  path_provider: ^2.1.5
  open_filex: ^4.5.0
  share_plus: ^10.1.2
```

---

## 3. Deep Linking Configuration

### Android (`android/app/src/main/AndroidManifest.xml`)
Inside the `<activity>` tag:
```xml
<!-- Custom Scheme: eclassify://store-qr/{token} -->
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="eclassify" android:host="store-qr" />
</intent-filter>

<!-- Universal Links / App Links: https://yourdomain.com/store-qr/{token} -->
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="https" android:host="yourdomain.com" android:pathPrefix="/store-qr" />
</intent-filter>
```

### iOS (`ios/Runner/Info.plist`)
```xml
<key>CFBundleURLTypes</key>
<array>
    <dict>
        <key>CFBundleTypeRole</key>
        <string>Editor</string>
        <key>CFBundleURLSchemes</key>
        <array>
            <string>eclassify</string>
        </array>
    </dict>
</array>
```

---

## 4. Feature 1: QR Scanner Screen (`QRScannerScreen.dart`)

Place a scanner icon in the App Bar / Search bar of the Home screen.

```dart
import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

class QRScannerScreen extends StatefulWidget {
  const QRScannerScreen({Key? key}) : super(key: key);

  @override
  State<QRScannerScreen> createState() => _QRScannerScreenState();
}

class _QRScannerScreenState extends State<QRScannerScreen> {
  final MobileScannerController _controller = MobileScannerController(
    detectionSpeed: DetectionSpeed.noDuplicates,
  );
  bool _isScanned = false;

  void _handleBarcode(BarcodeCapture capture) {
    if (_isScanned) return;
    final List<Barcode> barcodes = capture.barcodes;
    for (final barcode in barcodes) {
      final String? code = barcode.rawValue;
      if (code != null && code.isNotEmpty) {
        setState(() => _isScanned = true);
        _processScannedCode(code);
        break;
      }
    }
  }

  void _processScannedCode(String raw) {
    String identifier = raw;
    
    // Parse deep link e.g. eclassify://store-qr/sqr_xxx
    if (raw.contains('store-qr/')) {
      identifier = raw.split('store-qr/').last.split('?').first;
    }

    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (_) => SellerStoreQRScreen(identifier: identifier),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Scan Seller QR Code'),
        actions: [
          IconButton(
            icon: const Icon(Icons.flash_on),
            onPressed: () => _controller.toggleTorch(),
          ),
        ],
      ),
      body: Stack(
        children: [
          MobileScanner(
            controller: _controller,
            onDetect: _handleBarcode,
          ),
          // Custom overlay framing box
          Center(
            child: Container(
              width: 260,
              height: 260,
              decoration: BoxDecoration(
                border: Border.all(color: Theme.of(context).primaryColor, width: 3),
                borderRadius: BorderRadius.circular(20),
              ),
            ),
          ),
          Positioned(
            bottom: 40,
            left: 20,
            right: 20,
            child: Text(
              'Align QR code inside frame to view seller store and offers',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.white, backgroundColor: Colors.black54),
            ),
          ),
        ],
      ),
    );
  }
}
```

---

## 5. Feature 2: Dedicated Seller Store QR Screen (`SellerStoreQRScreen.dart`)

### API Request
Endpoint: `GET /api/seller-qr/store/{identifier}`  
Query params: `latitude`, `longitude`, `city`, `state`, `search`, `category_id`, `sort_by`, `page`.

### Location Warning UI Component
When `location_warning.warning == true`, render a sleek, styled warning banner at the top of the screen:

```dart
Widget _buildLocationWarning(Map<String, dynamic> warningData) {
  if (warningData['warning'] != true) return const SizedBox.shrink();

  return Container(
    margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
    padding: const EdgeInsets.all(12),
    decoration: BoxDecoration(
      color: Colors.amber.shade50,
      border: Border.all(color: Colors.amber.shade300),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(Icons.warning_amber_rounded, color: Colors.amber.shade900, size: 24),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Location Discrepancy Notice',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.amber.shade900,
                  fontSize: 13,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                warningData['message'] ?? 'This seller is not in your current browsing area.',
                style: TextStyle(fontSize: 12, color: Colors.amber.shade900),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}
```

### Action Bar (Call, WhatsApp, Directions, Share)
```dart
Widget _buildActionButtons(Map<String, dynamic> store) {
  return Row(
    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
    children: [
      if (store['contact'] != null)
        IconButton(
          icon: const Icon(Icons.phone, color: Colors.blue),
          onPressed: () => launchUrl(Uri.parse('tel:${store['contact']}')),
        ),
      if (store['contact'] != null)
        IconButton(
          icon: const Icon(Icons.chat, color: Colors.green),
          onPressed: () {
            final phone = store['contact'].toString().replaceAll(RegExp(r'[^0-9]'), '');
            launchUrl(Uri.parse('https://wa.me/$phone'));
          },
        ),
      if (store['latitude'] != null && store['longitude'] != null)
        IconButton(
          icon: const Icon(Icons.directions, color: Colors.orange),
          onPressed: () => launchUrl(Uri.parse('https://www.google.com/maps/search/?api=1&query=${store['latitude']},${store['longitude']}')),
        ),
      IconButton(
        icon: const Icon(Icons.share, color: Colors.purple),
        onPressed: () => Share.share('Check out ${store['name']} on Eclassify!'),
      ),
    ],
  );
}
```

---

## 6. Feature 3: Seller Dashboard -> "My Store QR Standee"

Sellers manage and download their QR standee from Profile -> My Store -> Store QR Code.

1. **Check Eligibility**:
   - `GET /api/seller-qr/eligibility` (Bearer token)
   - If `eligible == false`: Display "Upgrade Package" banner linking to `SubscriptionPackagesScreen`.
2. **Fetch Existing QR**:
   - `GET /api/seller-qr/my-qr`
3. **Customize & Generate**:
   - `POST /api/seller-qr/generate-or-update`
   - Fields: `title`, `tagline`, `primary_color`, `secondary_color`, `center_logo`
4. **Download & Share**:
   - Download PDF:
     `GET /api/seller-qr/download?token={token}&format=pdf&size=standee`
   - Save to device via `path_provider` and open with `open_filex`.
