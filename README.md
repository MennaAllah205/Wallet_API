1) Authentication & Access Control
I used Laravel Sanctum to secure the APIs and handle user authentication.
All wallet and services endpoints are protected using the auth:sanctum middleware, so only authenticated users can access them.

2) Wallet Operations & Business Logic

The wallet logic is implemented safely :

Charging wallet updates the user balance and records a transaction.

Transfer requests are created with a pending status.

Only the sender can confirm or cancel the transfer.

The sender’s balance is deducted and the receiver’s balance is increased only after confirmation.

I used DB::transaction to ensure data integrity and avoid any inconsistent balance updates.

3) Services CRUD & Purchase

I implemented full CRUD for services and added a purchase endpoint.
When a user buys a service:

The service price is deducted from the user balance.

A transaction is created with type purchase.

A notification is created for the user.

4) Notifications (Real-time)

I added a real-time notification system using Laravel Broadcasting with Pusher:

Notifications are created and broadcasted instantly after every transaction:

wallet charge

transfer request

transfer confirmation/cancellation

service purchase

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

I handled the main edge cases to ensure correct logic:

Prevent transferring money to yourself.

Prevent service purchase if balance is insufficient.

Prevent confirming a transfer that is already confirmed or cancelled.

Ensure only the sender can confirm or cancel the transfer.