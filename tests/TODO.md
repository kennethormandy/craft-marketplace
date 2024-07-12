# TODO

Could mock Social Login `EVENT_AFTER_FETCH_ACCESS_TOKEN` result from Stripe, which is probably a better way to test the Connect flow:

```json
{
  "name": "afterFetchAccessToken",
  "sender": {},
  "handled": false,
  "data": null,
  "provider": {
    "id": null,
    "dateCreated": null,
    "dateUpdated": null,
    "enabled": true,
    "loginEnabled": false,
    "cpLoginEnabled": false,
    "matchUserSource": "email",
    "matchUserDestination": "email",
    "fieldMapping": {
      "username": "",
      "email": "email",
      "name": "",
      "firstName": "",
      "lastName": "",
      "fullName": "",
      "photo": "",
      "field:platformConnectButton": "",
      "field:testing": ""
    },
    "authorizationOptions": [],
    "scopes": [],
    "customProfileFields": [],
    "config": [],
    "clientId": "$STRIPE_CONNECT_CLIENT_ID",
    "clientSecret": "$STRIPE_SECRET_KEY",
    "redirectUri": null
  },
  "ownerHandle": "social-login",
  "accessToken": {
    "livemode": false,
    "token_type": "bearer",
    "stripe_publishable_key": "pk_test_abc123",
    "stripe_user_id": "acct_1234567890123456",
    "scope": "express",
    "access_token": "sk_test_def456",
    "refresh_token": "rt_hij789"
  },
  "token": {
    "id": null,
    "ownerHandle": "social-login",
    "providerType": "kennethormandy\\marketplace\\providers\\StripeExpressProvider",
    "tokenType": "oauth2",
    "reference": null,
    "accessToken": "sk_test_def456",
    "secret": null,
    "expires": null,
    "refreshToken": "rt_hij789",
    "resourceOwnerId": null,
    "values": {
      "livemode": false,
      "token_type": "bearer",
      "stripe_publishable_key": "pk_test_abc123",
      "stripe_user_id": "acct_1234567890123456",
      "scope": "express"
    },
    "dateCreated": null,
    "dateUpdated": null,
    "uid": null
  }
}
```
