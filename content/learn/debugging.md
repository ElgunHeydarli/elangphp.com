# Find and fix a problem in Elang

A good debugging routine narrows the problem before changing code. This lesson gives you a repeatable sequence for both local setup and HTTP failures.

## Step 1: Record what failed

Write down the command or URL, the method, the status, and what you expected. A browser address bar sends GET. A JSON POST example needs an HTTP client or fetch request with the correct body and Content-Type.

Change one thing at a time. If you update PHP, change the database URL, and rewrite the route simultaneously, you lose the evidence that tells you which change mattered.

## Step 2: Check the environment

```bash
php -v
php elang doctor
php elang routes
```

Doctor checks requirements, private storage, key configuration, and database connectivity without showing credentials. The route list answers whether the application actually registered the path and method you are using.

## Step 3: Read the status code

- 404: inspect the path, parameter requirements, and web-server rewrite rule.
- 405: inspect the HTTP method and Allow header.
- 419: refresh the form and check its session-bound CSRF token.
- 422: inspect the field errors in the JSON response.
- 429: wait for Retry-After and review your request rate.
- 500: inspect the private server log using the response request ID.

Do not treat every failure as a database problem. Each status points to a different layer.

## Step 4: Inspect a safe reproduction

Replace an external API call or complicated controller with a small route returning a known array. If that works, add the next dependency back. Use temporary data when testing queries or forms.

Keep secrets out of screenshots, public issues, and pasted stack traces. A failing request can be described without sharing real credentials.

## Step 5: Verify the fix

Repeat the original request. Check the success case and the failure case you meant to handle. If you changed validation, test invalid input as well as valid input. If you changed a route, test the intended method and an unsupported one.

For a recurring bug, add a focused automated test. Then run the relevant test and the framework checks before publishing a change.

## Practice exercise

Temporarily request `/hello` using POST when it only has a GET route. Confirm a 405 response, read the route list, and identify the correct method. Restore the request without changing the route. You have fixed the caller rather than weakening the application's behavior.

Continue to [deployment preparation](/learn/ship), and keep the [troubleshooting reference](/docs/v0.1/troubleshooting) for specific error messages.
