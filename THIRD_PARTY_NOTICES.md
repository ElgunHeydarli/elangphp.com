# Third-party assets and components

PHP package licenses are recorded in Composer metadata. Run `composer licenses` for the resolved dependency list. Optional WebSocket runtime uses Workerman under its upstream MIT license.

The website's locally served national flag images were retrieved from Flagcdn:

- English language selector (United Kingdom): https://flagcdn.com/gb.svg
- Azerbaijani language selector: https://flagcdn.com/az.svg
- Russian language selector: https://flagcdn.com/ru.svg

Flag image service: https://flagpedia.net/download/api. The flags identify language choices in the interface; locale URLs remain language codes.

The optional ORM integration uses Doctrine ORM (MIT); ORM capabilities are provided by Doctrine. The outbound HTTP client uses Guzzle (MIT). Their resolved dependency licenses remain available through Composer metadata.
