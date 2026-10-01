# omnibus/aramex

Aramex for [glitchr/omnibus](https://github.com/glitchr-studio/omnibus): rates (RateCalculator),
shipments with their labels (Shipping), tracking (Tracking) and offices (Location) - the JSON
web services on ws.aramex.net.

```yaml
omnibus:
    gateways:
        aramex:
            factory: aramex
            options:
                username: '%env(ARAMEX_USERNAME)%'
                password: '%env(ARAMEX_PASSWORD)%'
                account_number: '%env(ARAMEX_ACCOUNT)%'
                account_pin: '%env(ARAMEX_PIN)%'
                account_entity: '%env(ARAMEX_ENTITY)%'     # the account's station, e.g. DXB
                account_country: '%env(ARAMEX_COUNTRY)%'   # e.g. AE
                sandbox: true
                rates: [...]                               # optional: configured prices instead of CalculateRate
```

The service is the product (PPX Priority Parcel Express, EPX Economy Parcel Express, PDX
documents, OND / CDS domestic). Rating asks once per product (option `products`). Shipment
options: `label_format` (PDF, ZPL), `description`, `instructions`, `services` (Aramex's codes,
comma-separated), `currency` (for rates).

Credentials: an Aramex account with web services access (your account manager enables it) gives
the username, password, account number, PIN, entity and country; the sandbox has its own.

Built from Aramex's published web services documentation and tested on recorded answers; not yet
run against the sandbox: that needs the credentials above.

License: LGPL-3.0-or-later.
