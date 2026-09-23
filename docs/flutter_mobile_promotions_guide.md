# Flutter Mobile App Implementation Guide: Sales & Offer Page, Promotions & Campaigns

This guide provides step-by-step instructions for integrating the **Sales / Offer Page**, **Promotions**, **Campaigns**, and **"Promote this Ad"** features in your Flutter mobile application.

---

## 1. Overview & Architecture

The mobile app should feature:
1. **Offers / Sales Page (Offer Zone)**:
   - Header carousel of active **Campaigns** & **Spotlight Ads**.
   - Tabbed / sectioned view for:
     - ⚡ **Flash Sales**: Urgency-driven countdown timer cards with stock progress indicators.
     - 🏷️ **Deals of the Day**: 24-hour deals refreshing every midnight with countdown clock.
     - 📦 **Stock Clearance Sale**: Heavy discounts with limited inventory warnings.
   - **Location-aware filtering**: Passed automatically using user's current GPS coordinates (`latitude`, `longitude`, `radius`) or selected City/Area.
2. **Item Detail Screen Enhancements**:
   - Promotion badge (e.g. `40% OFF - Flash Sale`), countdown timer ticker, original vs discounted price, and remaining stock progress bar.
3. **Seller "My Items" Management**:
   - **"Promote this Ad"** modal action button with 3 options:
     - 🚀 **Daily Bump Up**: Pushes ad back to top every 24 hours.
     - ⭐ **Top Ad**: Sticky top placement with prominent badge.
     - 🔦 **Spotlight**: Features ad on top of offer zone & home carousel.
   - **"Add to Promotion"** action to submit item to ongoing Flash Sale or Clearance Sale.

---

## 2. API Endpoints Reference

Base URL: `https://your-domain.com/api`

### 2.1 Public Offer Endpoints

| Method | Endpoint | Parameters | Purpose |
|---|---|---|---|
| `GET` | `/offers/campaigns` | `status=active` | Returns active campaign banners |
| `GET` | `/offers/campaign-detail` | `id` or `slug` | Campaign details & child promotions |
| `GET` | `/offers/promotions` | `campaign_id` (optional), `type` | List promotions |
| `GET` | `/offers/flash-sales` | `city`, `latitude`, `longitude`, `radius` | Flash sale items with countdown |
| `GET` | `/offers/deals-of-the-day` | `city`, `latitude`, `longitude`, `radius` | 24-hour daily deals |
| `GET` | `/offers/clearance-sales` | `city`, `latitude`, `longitude`, `radius` | Clearance items with stock count |
| `GET` | `/offers/spotlight-ads` | `city`, `latitude`, `longitude`, `radius` | Promoted spotlight ads |

### 2.2 Seller / Authenticated Endpoints (Include `Authorization: Bearer <Token>`)

| Method | Endpoint | Parameters / Body | Purpose |
|---|---|---|---|
| `GET` | `/seller/promotions/available` | — | Available promotions open for item submissions |
| `GET` | `/seller/promotions/my-items` | `page` | Seller's submitted items |
| `POST` | `/seller/promotions/add-item` | `promotion_id`, `item_id`, `discounted_price`, `stock_quantity`, `valid_until` | Submit item to promotion |
| `POST` | `/seller/promotions/update-item` | `id`, `discounted_price`, `stock_quantity` | Update price or stock |
| `POST` | `/seller/promotions/delete-item` | `id` | Remove item from promotion |
| `GET` | `/seller/items/promotion-options` | `item_id` | Check subscription package quota for bumping / top ad / spotlight |
| `POST` | `/seller/items/promote` | `item_id`, `promotion_type`, `duration_days` | Boost the item |

---

## 3. Data Models (Dart)

### 3.1 `CampaignModel`
```dart
class CampaignModel {
  final int id;
  final String title;
  final String slug;
  final String? description;
  final String? bannerImage;
  final String? mobileBanner;
  final String? highlightBadge;
  final double? discountPercentage;
  final DateTime startDate;
  final DateTime endDate;
  final List<PromotionModel>? promotions;

  CampaignModel({
    required this.id,
    required this.title,
    required this.slug,
    this.description,
    this.bannerImage,
    this.mobileBanner,
    this.highlightBadge,
    this.discountPercentage,
    required this.startDate,
    required this.endDate,
    this.promotions,
  });

  factory CampaignModel.fromJson(Map<String, dynamic> json) {
    return CampaignModel(
      id: json['id'],
      title: json['title'] ?? '',
      slug: json['slug'] ?? '',
      description: json['description'],
      bannerImage: json['banner_image'],
      mobileBanner: json['mobile_banner'] ?? json['banner_image'],
      highlightBadge: json['highlight_badge'],
      discountPercentage: json['discount_percentage'] != null ? (json['discount_percentage'] as num).toDouble() : null,
      startDate: DateTime.parse(json['start_date']),
      endDate: DateTime.parse(json['end_date']),
      promotions: json['promotions'] != null
          ? (json['promotions'] as List).map((p) => PromotionModel.fromJson(p)).toList()
          : null,
    );
  }
}
```

### 3.2 `PromotionItemModel`
```dart
class PromotionItemModel {
  final int id;
  final int promotionId;
  final int itemId;
  final double originalPrice;
  final double discountedPrice;
  final double discountPercentage;
  final int? stockQuantity;
  final int? remainingQuantity;
  final DateTime? validUntil;
  final int countdownRemainingSeconds;
  final double? distanceInKm;
  final Map<String, dynamic>? item;
  final Map<String, dynamic>? seller;

  PromotionItemModel({
    required this.id,
    required this.promotionId,
    required this.itemId,
    required this.originalPrice,
    required this.discountedPrice,
    required this.discountPercentage,
    this.stockQuantity,
    this.remainingQuantity,
    this.validUntil,
    required this.countdownRemainingSeconds,
    this.distanceInKm,
    this.item,
    this.seller,
  });

  factory PromotionItemModel.fromJson(Map<String, dynamic> json) {
    return PromotionItemModel(
      id: json['id'],
      promotionId: json['promotion_id'],
      itemId: json['item_id'],
      originalPrice: (json['original_price'] as num).toDouble(),
      discountedPrice: (json['discounted_price'] as num).toDouble(),
      discountPercentage: (json['discount_percentage'] as num).toDouble(),
      stockQuantity: json['stock_quantity'],
      remainingQuantity: json['remaining_quantity'],
      validUntil: json['valid_until'] != null ? DateTime.tryParse(json['valid_until']) : null,
      countdownRemainingSeconds: json['countdown_remaining_seconds'] ?? 0,
      distanceInKm: json['distance_in_km'] != null ? (json['distance_in_km'] as num).toDouble() : null,
      item: json['item'],
      seller: json['seller'],
    );
  }
}
```

---

## 4. Real-time Countdown Timer Widget

Here is a reusable countdown widget that ticks every second until expiry:

```dart
import 'dart:async';
import 'package:flutter/material.dart';

class CountdownTimerWidget extends StatefulWidget {
  final int initialSeconds;
  final TextStyle? style;
  final VoidCallback? onTimerFinished;

  const CountdownTimerWidget({
    Key? key,
    required this.initialSeconds,
    this.style,
    this.onTimerFinished,
  }) : super(key: key);

  @override
  State<CountdownTimerWidget> createState() => _CountdownTimerWidgetState();
}

class _CountdownTimerWidgetState extends State<CountdownTimerWidget> {
  late int _remainingSeconds;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _remainingSeconds = widget.initialSeconds;
    _startTimer();
  }

  void _startTimer() {
    if (_remainingSeconds <= 0) return;
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remainingSeconds > 0) {
        setState(() {
          _remainingSeconds--;
        });
      } else {
        _timer?.cancel();
        if (widget.onTimerFinished != null) {
          widget.onTimerFinished!();
        }
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  String _formatDuration(int totalSeconds) {
    final days = totalSeconds ~/ 86400;
    final hours = (totalSeconds % 86400) ~/ 3600;
    final minutes = (totalSeconds % 3600) ~/ 60;
    final seconds = totalSeconds % 60;

    if (days > 0) {
      return '${days}d ${hours.toString().padLeft(2, '0')}h ${minutes.toString().padLeft(2, '0')}m';
    }
    return '${hours.toString().padLeft(2, '0')}:${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    if (_remainingSeconds <= 0) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(color: Colors.grey.shade400, borderRadius: BorderRadius.circular(4)),
        child: const Text('EXPIRED', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
      );
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.red.shade600,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.timer_outlined, color: Colors.white, size: 14),
          const SizedBox(width: 4),
          Text(
            _formatDuration(_remainingSeconds),
            style: widget.style ?? const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }
}
```

---

## 5. Location Integration

Always pass current user location query parameters when fetching promotional items:

```dart
Map<String, dynamic> buildLocationParams() {
  final userLocation = LocationService.currentLocation; // e.g. from geolocator package
  final selectedCity = LocationService.selectedCity;

  final Map<String, dynamic> params = {};

  if (userLocation != null) {
    params['latitude'] = userLocation.latitude;
    params['longitude'] = userLocation.longitude;
    params['radius'] = 50; // 50 km default radius
  }

  if (selectedCity != null && selectedCity.isNotEmpty) {
    params['city'] = selectedCity;
  }

  return params;
}

// Fetch Flash Sales:
Future<List<PromotionItemModel>> fetchFlashSales() async {
  final params = buildLocationParams();
  final response = await ApiClient.get('/offers/flash-sales', queryParameters: params);
  final data = response.data['data']['items'] as List;
  return data.map((e) => PromotionItemModel.fromJson(e)).toList();
}
```

---

## 6. "Promote this Ad" Action Flow (Seller Profile)

When a seller views their item in "My Ads" or item detail:

1. Request `/seller/items/promotion-options?item_id={itemId}`:
   - Check `options.daily_bump_up.is_allowed`, `options.top_ad.is_allowed`, and `options.spotlight.is_allowed`.
   - If allowed: display active button with remaining quota count.
   - If not allowed: display "Upgrade Package" button redirecting to Subscription Package purchase screen.
2. When seller taps **"Boost"**:
   - Call `/seller/items/promote`:
     ```json
     {
       "item_id": 123,
       "promotion_type": "top_ad",
       "duration_days": 7
     }
     ```
   - On success: show confetti/success dialog: *"Your ad has been successfully boosted to Top Ad!"*.
