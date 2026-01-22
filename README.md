1) Authentication & Access Control
Laravel Sanctum is used to secure the APIs and handle user authentication.
All wallet and services endpoints are protected using auth:sanctum middleware, so only authenticated users can access them.

2) Wallet Operations & Business Logic

The wallet logic is implemented safely:

Charging wallet updates the user balance and records a transaction.

Transfer requests are created with a pending status.

Only the sender can confirm or cancel the transfer.

The sender's balance is deducted and the receiver's balance is increased only after confirmation.

DB::transaction is used to ensure data integrity and avoid any inconsistent balance updates.

3) Services CRUD & Purchase

Full CRUD is implemented for services with a purchase endpoint.
When a user buys a service:

The service price is deducted from the user balance.

A transaction is created with type purchase.

A notification is created for the user.

4) Notifications (Real-time)

A real-time notification system is implemented using Laravel Broadcasting with Pusher:

Notifications are created and broadcasted instantly after every transaction:

- Wallet charge
- Transfer request
- Transfer confirmation/cancellation
- Service purchase

Real-time Implementation:

Uses Laravel Broadcasting with Pusher WebSocket

NotificationEvent class handles broadcasting to private channels

Each user receives notifications on their private channel (user.{id})

Frontend can listen to real-time notifications using Pusher.js

Endpoints:

GET /notifications - Fetch all user notifications

POST /broadcast-notification - Send custom real-time notification

All wallet operations (charge, transfer, purchase) automatically trigger real-time notifications

5) Edge Cases & Validation

The main edge cases are handled to ensure correct logic:

- Prevent transferring money to yourself
- Prevent service purchase if balance is insufficient
- Prevent confirming a transfer that is already confirmed or cancelled
- Ensure only the sender can confirm or cancel the transfer

6) Rate Limiting & Security

Advanced rate limiting is implemented to protect the API from abuse and attacks:

Custom Rate Limiter Middleware:
- Creates unique signature for each request based on IP, method, and path
- Tracks attempts per minute with configurable limits
- Returns 429 status with retry-after information
- Adds rate limit headers to responses

Rate Limits Applied:

Public Endpoints (No Authentication):
- Register: 3 attempts per minute (prevents spam registrations)
- Login: 5 attempts per minute (prevents brute force attacks)

Authenticated Endpoints (With Authentication):
- General: 60 requests per minute (base limit for authenticated users)
- Wallet Charge: 10 attempts per minute (financial operations)
- Transfer Request: 5 attempts per minute (most sensitive operation)
- Transfer Confirm/Cancel: 10 attempts per minute
- Service Purchase: 15 attempts per minute (financial operations)
- Broadcast Notification: 10 attempts per minute (prevents spam)
- Services CRUD: 20 attempts per minute (administrative operations)
- Transactions/Notifications: 30 attempts per minute (data reading)
- User Profile: 60 requests per minute (user data)
- Logout: 20 attempts per minute

Security Features:
- IP-based tracking with unique request signatures
- Configurable time windows and attempt limits
- Detailed error messages with retry timing
- Rate limit information in response headers
- Protection against common attack vectors

Example Response When Rate Limited:
```json
{
    "status": false,
    "message": "Too many attempts. Please try again later.",
    "retry_after": 45,
    "available_in": "45 seconds"
}
```