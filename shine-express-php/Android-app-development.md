# Service Management Platform - Mobile App Requirements

This document defines the features for the **Customer Android App** and **Staff Android App** for a multi-service business providing services such as:

- House Cleaning
- Car Cleaning
- Water Tank Cleaning
- Sofa Cleaning
- Carpet Cleaning
- Bathroom Cleaning
- Kitchen Cleaning
- Deep Cleaning
- Pest Control

---

# Customer Android App

The Customer App allows customers to browse services, book appointments, track service progress, and manage their account.

## 1. Authentication

- Sign Up
- Login
- Email OTP Verification
- Forgot Password
- Manage Profile

---

## 2. Home Screen

- Service Categories
- Featured Services
- Special Offers
- Upcoming Bookings
- Recent Bookings
- Search Services

---

## 3. Services

Display all available services with complete details.

### Service Information

- Service Name
- Description
- Images
- Starting Price
- Estimated Duration
- What's Included
- FAQs
- Customer Ratings & Reviews

---

## 4. Booking

Customers can:

- Select Service
- Select Package
- Choose Date
- Choose Time Slot
- Select Address
- Add Special Instructions
- Upload Reference Images (Optional)
- Confirm Booking

---

## 5. Booking Status

Customers can track booking progress.

### Booking Flow

Pending

↓

Confirmed

↓

Staff Assigned

↓

Work Started

↓

Work Completed

↓

Review Submitted

---

## 6. Booking History

Customers can:

- View Previous Bookings
- View Booking Details
- Download Invoice
- Rebook Previous Services
- Raise Complaint

---

## 7. Reviews & Ratings

After service completion:

- Give Rating
- Write Review
- Upload Service Photos (Optional)

---

## 8. Notifications

Push notifications for:

- Booking Confirmation
- Staff Assigned
- Service Started
- Service Completed
- Promotional Offers

---

## 9. Support

- Call Support
- WhatsApp Support
- FAQs
- Raise Support Ticket

---

## 10. Referral Program

- Refer Friends
- Earn Reward Points
- Redeem Coupons

---

## 11. Loyalty Program

- Reward Points
- Membership Plans
- Exclusive Offers

---

## 12. Profile

Customers can manage:

- Personal Information
- Saved Addresses
- Notification Preferences
- Privacy Settings

---

# Staff Android App

The Staff App is designed to help field employees efficiently manage assigned jobs.

---

## 1. Authentication

- Login
- Employee ID
- Password

---

## 2. Dashboard

Display:

- Today's Jobs
- Upcoming Jobs
- Completed Jobs
- Attendance Status

---

## 3. Assigned Jobs

Each job includes:

- Customer Name
- Customer Contact Number
- Address
- Service Details
- Scheduled Date & Time
- Estimated Duration
- Special Instructions

---

## 4. Job Management

Staff can:

- Accept Job
- Reject Job (with reason)
- Request Reschedule

---

## 5. Navigation

- Open Customer Address in Google Maps

---

## 6. Start Service

Before starting:

- Start Job Timer
- Upload Before-Service Photos

---

## 7. During Service

Staff can:

- Add Work Notes
- Upload Progress Photos
- Complete Service Checklist
- Report Additional Issues

---

## 8. Complete Service

Before completion:

- Upload After-Service Photos
- Add Completion Notes
- Mark Job as Completed

---

## 9. Attendance

Staff can:

- Check In
- Check Out
- GPS Verification (Optional)
- Selfie Verification (Optional)

---

## 10. Leave Management

Staff can:

- Apply Leave
- View Leave History
- Check Leave Status

---

## 11. Notifications

Receive notifications for:

- New Job Assignment
- Schedule Changes
- Leave Approval
- Company Announcements

---

## 12. Profile

Manage:

- Personal Information
- Profile Photo
- Documents
- ID Card
- Skills
- Certifications
- Emergency Contact

---

# Features Common to Both Apps

- Secure Authentication
- Push Notifications
- Dark Mode
- Multi-language Support
- Offline Data Sync (when internet is available)
- Profile Management
- Help & Support
- Feedback & Suggestions
- App Update Notifications

---

# Future Enhancements

## AI Features

- AI Chatbot for Customer Support
- AI-Based Service Recommendations
- AI Cleaning Checklist
- Image-Based Service Estimation

---

## Business Automation

- Smart Staff Assignment
- Automatic Booking Reminders
- Service Scheduling Optimization
- Subscription Plans
- Corporate Accounts

---

# Technology Stack

## Mobile Apps

- **Flutter** (Android + iOS) — project: `../shine-express-flutter`

## Backend

- **PHP + MySQL** REST API (`/api/v1`) — project: `shine-express-php`
- See [docs/MOBILE_API.md](docs/MOBILE_API.md)

## Notifications

- Firebase Cloud Messaging (device token API ready; wire FCM SDK in Flutter when keys are available)

## Maps

- Google Maps SDK (API key in AndroidManifest / iOS AppDelegate)
- Setup guide: [docs/GOOGLE_MAPS.md](docs/GOOGLE_MAPS.md)

---

# Implementation status

| Area | Status |
|------|--------|
| Customer auth (login, register + OTP, forgot password, profile) | ✅ Flutter + API |
| Home (categories, offers, featured, search, bookings) | ✅ |
| Service detail (packages, FAQs, reviews) | ✅ |
| Booking + history + complete/review | ✅ |
| Support tickets + call/WhatsApp | ✅ |
| Loyalty / referral | ✅ |
| Staff dashboard, jobs, accept/reject, photos, notes, checklist | ✅ |
| Attendance + leave | ✅ |
| Dark mode | ✅ |
| Push (FCM), offline sync, i18n | ⏳ API hooks / later |
