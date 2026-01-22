1) Authentication & Access Control

Laravel Sanctum is used to secure the APIs and handle user authentication.

All wallet and services endpoints are protected using auth:sanctum middleware.

Only authenticated users can access these endpoints.

2) Wallet Operations & Business Logic

Wallet operations are implemented with safe and accurate business logic:

Charge Wallet

Updates user balance.

Records a transaction.

P2P Transfer

Transfer requests are created with pending status.

Only the sender can confirm or cancel the transfer.

Balance is updated only after confirmation:

Sender balance is deducted.

Receiver balance is increased.

DB::transaction is used to ensure data integrity and avoid inconsistent updates.

3) Services CRUD & Purchase

Full CRUD for services is implemented.

Users can purchase services if they have sufficient balance.

On purchase:

Service price is deducted from user balance.

A transaction is created with type purchase.

A notification is created for the user.

4) Notifications (Real-time)

Real-time notifications are implemented using Laravel Broadcasting with Pusher:

Notifications are created and broadcasted after each transaction:

Wallet charge

Transfer request

Transfer confirm/cancel

Service purchase

Notifications are broadcasted to private channels:

user.{id}

Endpoints:

GET /notifications – fetch user notifications

POST /broadcast-notification – send custom notification

5) Edge Cases & Validation

The system handles important edge cases to ensure correct logic:

Prevent transferring money to yourself

Prevent purchasing service if balance is insufficient

Prevent confirming/canceling an already processed transfer

Ensure only the sender can confirm or cancel the transfer

6) Rate Limiting & Security

A custom rate limiter middleware is implemented to protect the API from abuse:

Unique signature for each request based on IP, method, and path

Tracks attempts per minute

Returns 429 Too Many Attempts with retry timing

Adds rate limit headers to responses

Applied Rate Limits:

Public Endpoints

Endpoint	Limit
Register	3/min
Login	5/min

Authenticated Endpoints

Endpoint	Limit
General	60/min
Wallet Charge	10/min
Transfer Request	5/min
Transfer Confirm/Cancel	10/min
Service Purchase	15/min
Services CRUD	20/min
Transactions/Notifications	30/min
User Profile	60/min
Logout	20/min

Example Response When Rate Limited:
```json
{
    "status": false,
    "message": "Too many attempts. Please try again later.",
    "retry_after": 45,
    "available_in": "45 seconds"
}
```

## Postman Collection
The Postman collection is included in the repository:
`/postman/Wallet_API.postman_collection.json`
`/Wallet System.postman_collection.json`
