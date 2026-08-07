# Google Maps Integration (Shine Express)

Guide for obtaining a **production Google Maps API key** and wiring it into the Flutter app (`shine-express-flutter`) for Android and iOS.

Backend remains **PHP + MySQL**. Maps run on the device via the Flutter plugin `google_maps_flutter`. Address coordinates already come from the API (`latitude` / `longitude` on addresses and job details).

---

## What Maps are used for

| App | Use |
|-----|-----|
| Customer | Pick / show service address on a map |
| Staff | Show job location; open navigation; optional attendance GPS (via `geolocator`) |

Package already declared in `shine-express-flutter/pubspec.yaml`:

```yaml
google_maps_flutter: ^2.5.3
geolocator: ^10.1.0
```

---

## 1. Create a Google Cloud project

1. Open [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project (e.g. `shine-express-prod`) or select an existing one
3. Link a **billing account** (required for Maps in production)

Official: [Get an API key (Android)](https://developers.google.com/maps/documentation/android-sdk/get-api-key)

---

## 2. Enable required APIs

**APIs & Services → Library** — enable:

| API | Required? | Why |
|-----|-----------|-----|
| Maps SDK for Android | Yes | Map widget on Android |
| Maps SDK for iOS | Yes | Map widget on iOS |
| Geocoding API | Optional | Address → lat/lng if you add server-side or client geocoding |
| Places API | Optional | Address autocomplete |
| Directions API | Optional | Turn-by-turn / route polyline |

For a first production release, **Maps SDK for Android + Maps SDK for iOS** is enough if you already store lat/lng in MySQL.

---

## 3. Create API keys

**APIs & Services → Credentials → Create credentials → API key**

Recommended: create **two keys** (easier to restrict):

1. `shine-express-android-maps`
2. `shine-express-ios-maps`

Copy each key once; treat them as secrets.

---

## 4. Restrict keys (production checklist)

### Android key

**Application restrictions → Android apps**

| Field | Value |
|-------|--------|
| Package name | `com.shineexpress.shine_express_app` |
| SHA-1 | Debug and/or release fingerprints (see below) |

**API restrictions → Restrict key**

- Maps SDK for Android  
- (plus any other Android Maps APIs you enabled)

#### Get SHA-1 fingerprints

**Debug** (local emulator / USB debug):

```bash
keytool -list -v \
  -keystore ~/.android/debug.keystore \
  -alias androiddebugkey \
  -storepass android \
  -keypass android
```

**Release** (your upload keystore):

```bash
keytool -list -v \
  -keystore /path/to/your-release.keystore \
  -alias YOUR_ALIAS
```

If you publish on **Google Play** with Play App Signing, also add the **App signing certificate SHA-1** from Play Console → App integrity.

### iOS key

**Application restrictions → iOS apps**

| Field | Value |
|-------|--------|
| Bundle ID | `com.shineexpress.shineExpressApp` |

**API restrictions → Restrict key**

- Maps SDK for iOS  
- (plus other iOS Maps APIs you enabled)

---

## 5. Add the key to the Flutter project

### Android

File: `shine-express-flutter/android/app/src/main/AndroidManifest.xml`

Replace the placeholder:

```xml
<meta-data
    android:name="com.google.android.geo.API_KEY"
    android:value="YOUR_ANDROID_MAPS_API_KEY"/>
```

Permissions already present:

- `INTERNET`
- `ACCESS_FINE_LOCATION`
- `ACCESS_COARSE_LOCATION`

### iOS

1. Add the Maps SDK key in `ios/Runner/AppDelegate.swift`:

```swift
import Flutter
import UIKit
import GoogleMaps

@UIApplicationMain
@objc class AppDelegate: FlutterAppDelegate {
  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
  ) -> Bool {
    GMSServices.provideAPIKey("YOUR_IOS_MAPS_API_KEY")
    GeneratedPluginRegistrant.register(with: self)
    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }
}
```

2. Confirm privacy strings in `ios/Runner/Info.plist` (already added for Shine Express):

- `NSLocationWhenInUseUsageDescription`
- Camera / photo strings if you use job photos

3. Run `cd ios && pod install` after enabling Maps (CocoaPods pulls GoogleMaps).

---

## 6. Flutter usage (widget sketch)

Coordinates from PHP API (job / address):

```dart
import 'package:google_maps_flutter/google_maps_flutter.dart';

GoogleMap(
  initialCameraPosition: CameraPosition(
    target: LatLng(latitude, longitude),
    zoom: 15,
  ),
  markers: {
    Marker(
      markerId: const MarkerId('job'),
      position: LatLng(latitude, longitude),
      infoWindow: InfoWindow(title: addressLabel),
    ),
  },
  myLocationEnabled: true,
  myLocationButtonEnabled: true,
);
```

Open external navigation (no Directions API required):

```dart
// Google Maps app / Apple Maps via url_launcher
final uri = Uri.parse(
  'https://www.google.com/maps/dir/?api=1&destination=$lat,$lng',
);
```

---

## 7. Backend (PHP) notes

No Google Maps key is required on the PHP server for basic map display.

Store and return:

- `addresses.latitude`, `addresses.longitude`
- Job detail already exposes these under `address.latitude` / `address.longitude` (`GET /api/v1/jobs/{id}`)

If you later geocode on the server, use a **separate server key** restricted by IP, never the mobile SDK keys.

---

## 8. Billing, quotas, and security

- Maps includes a monthly Google Cloud credit; monitor **Billing** and set **budget alerts**
- Never commit unrestricted keys to a public Git repo
- Prefer local overrides or CI secrets for production keys
- Rotate a key immediately if it leaks
- Keep API restrictions + app restrictions enabled

---

## 9. Verify integration

1. Key restricted + correct APIs enabled  
2. Android: cold-start app → open a screen with `GoogleMap` → map tiles load (not blank / gray)  
3. iOS: same on simulator/device  
4. If blank map: check Logcat / Xcode for `API_KEY` / billing / restriction errors  

Common failures:

| Symptom | Likely cause |
|---------|----------------|
| Gray map / blank | Wrong key, billing off, or SDK not enabled |
| Works in debug, fails in release | Missing release / Play SHA-1 on Android key |
| Works on Android, fails on iOS | iOS key not set in `AppDelegate`, or wrong bundle ID |

---

## 10. Related project files

| File | Role |
|------|------|
| `shine-express-flutter/android/app/src/main/AndroidManifest.xml` | Android Maps API key meta-data |
| `shine-express-flutter/ios/Runner/AppDelegate.swift` | iOS `GMSServices.provideAPIKey` |
| `shine-express-flutter/pubspec.yaml` | `google_maps_flutter` dependency |
| `shine-express-php/docs/MOBILE_API.md` | Address / job lat-lng API |
| `shine-express-php/Android-app-development.md` | Product map requirements |

---

## Quick checklist

- [ ] Google Cloud project + billing  
- [ ] Maps SDK Android + iOS enabled  
- [ ] Restricted Android key (package + SHA-1)  
- [ ] Restricted iOS key (bundle ID)  
- [ ] Key in `AndroidManifest.xml`  
- [ ] Key in `AppDelegate.swift`  
- [ ] Map screen shows tiles on device  
- [ ] Budget alert configured  
