# Your Elang task board

If created with Composer create-project or the global Elang installer, dependencies and your application key are already prepared. Run `php elang doctor`, then `php elang serve`, and open http://127.0.0.1:8000.

If generated from a contributor checkout with --local, first run composer install and php elang key:generate.

Add a task, mark it complete, and remove it. Each change uses POST, a session-bound CSRF token, backend validation, and escaped HTML. The example stores at most 20 temporary tasks per browser session. It has no accounts or shared database.

Walkthrough: https://elangphp.com/learn/build-a-task-board
