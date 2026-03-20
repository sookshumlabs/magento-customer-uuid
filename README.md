# QuarryTeam_CustomerUuid

Adds a `uuid` attribute to customers:

- Stored in dedicated customer UUID EAV backend table with DB-level unique UUID constraint.
- Automatically generated for existing customers on install and for new customers on save.
- Exposed via GraphQL on the `Customer` type as `uuid` for authenticated customers.
- Displayed on the Admin customer grid.
- Visible in Admin customer edit form (customer account section) and treated as immutable once set.
- UUID generation uses `ramsey/uuid` (v4).

## Installation (composer)

To install via composer:

```bash
composer require quarryteam/module-customer-uuid
bin/magento module:enable QuarryTeam_CustomerUuid
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento indexer:reindex customer_grid
bin/magento cache:clean
```

## GraphQL usage
API Access
1) Create a customer token
```
curl -X POST "http://<magento-base-url>/graphql/"" \\

-H "Content-Type: application/json" \\

-d '{"query":"mutation generateCustomerToken(\n  $email: String!,\n  $password: String!\n) {\n  generateCustomerToken(\n    email: $email,\n    password: $password\n  ) {\n    token\n  }\n}","variables":{"email":"<customer email>","password":"<customer password>"}}'
```
Response: a token string (JWT-like value depending on setup).

2) Query UUID via GraphQL
Use the token in the Authorization header:
```
curl -X POST "https://<magento-base-url>/graphql" \\

-H "Content-Type: application/json" \\

-H "Authorization: Bearer <customer-token>" \\

-d '{"query":"{ customer { email uuid } }"}'
```
Example GraphQL query:
```
{
  customer {
    email
    uuid
  }
}
```
## Tests

### Unit tests (module-local)

Run PHPUnit for the module unit tests (requires Magento unit test framework setup):

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist vendor/quarryteam/module-customer-uuid/Test/Unit
```

