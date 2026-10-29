# Build a complete task board with Elang

This project connects routes, middleware, controllers, validation, sessions, CSRF, and HTML templates in one small application. You will add a task, mark it complete, and remove it without a JavaScript framework.

The example stores up to 20 tasks in your browser session. It has no accounts or shared database, and tasks can disappear when the session expires. That boundary keeps the first complete project focused on the HTTP lifecycle.

## What you should know first

Complete [environment setup](/learn/environment), [your first route](/learn/first-route), and [validation](/learn/validate-input). You should be able to run PHP and Composer, edit a PHP file, and recognize an HTML form.

You can [try the live task board](/demo/task-board) before reading the code. Each browser session has its own task list; no sample account or login password is needed.

## Step 1: Generate the complete application

From the parent directory where you want the project, run:

```bash
composer create-project --repository=https://elangphp.com/packages elang/task-board task-board 0.1.0-dev
cd task-board
php elang doctor
php elang serve
```

Open `http://127.0.0.1:8000`. You should see the task board, an empty state, and an input labeled “What would you like to do?” No database migration or Node.js installation is needed for this particular example.

If the target directory already contains files, the generator refuses to overwrite it. Choose a new directory rather than deleting unrelated work.

After the one-time global CLI setup, you can also create this project with `elang new task-board --example=task-board`. Composer installs dependencies and generates the application key automatically.

## Step 2: Understand the project files

```text
app/Controllers/TaskBoardController.php  Read and change the task list
bootstrap/app.php                      Create Elang and load routes
config/app.php                         Set the board's base path
routes/web.php                         Register GET and POST routes
resources/views/task-board.twig        Render the HTML page
public/assets/task-board.css           Style the page
storage/sessions/                      Private server-side session files
```

Open these files in that order. The generated application contains the complete source, so you do not need to reconstruct missing methods from isolated snippets.

## Step 3: Follow one request

The GET route renders the current list. POST routes create, toggle, and delete tasks:

```php
$router->get('/', [TaskBoardController::class, 'index'], 'board');
$router->post('/add', [TaskBoardController::class, 'store']);
$router->post('/toggle/{id}', [TaskBoardController::class, 'toggle'])
    ->where('id', '[a-f0-9]{16}');
$router->post('/delete/{id}', [TaskBoardController::class, 'delete'])
    ->where('id', '[a-f0-9]{16}');
```

They run inside a group with rate limiting, session middleware, and CSRF middleware. Session middleware establishes the session before CSRF checks a token. The route then resolves the controller and its typed dependencies.

## Step 4: Validate before storing

The store handler reads the title and applies three constraints: it must not be blank, it must be a string, and it must be no longer than 100 characters.

```php
$valid = $validator->validate(['title' => $title], [
    'title' => [
        new Assert\NotBlank(),
        new Assert\Type('string'),
        new Assert\Length(max: 100),
    ],
]);
```

The full controller catches ValidationException and renders the form with useful field errors and the original input. The server returns 422 for invalid data. The browser's required and maxlength attributes are convenient, but the backend checks remain necessary because a caller can bypass browser validation.

## Step 5: Keep changes in the session

The controller reads `tasks` from the current session. It creates a random task ID, stores the approved title, and starts the task as incomplete:

```php
$id = bin2hex(random_bytes(8));
$tasks[$id] = ['id' => $id, 'title' => $valid['title'], 'done' => false];
$request->getSession()->set('tasks', $tasks);
```

Toggle and delete operate only on that session's list. An ID copied from another browser's list is not a task in your session and returns 404. Session isolation is useful here, but it is not the same thing as authenticating a user.

## Step 6: Protect the forms

Each form contains a hidden `_token` value generated from the current session:

```twig
<form action="/add" method="post">
  <input type="hidden" name="_token" value="{{ token }}">
  <label for="title">Task title</label>
  <input id="title" name="title" maxlength="100" required>
  <button type="submit">Add task</button>
</form>
```

A missing or incorrect token produces 419 before the handler changes state. Task titles are rendered with Twig's automatic HTML escaping. Do not add `|raw` to user-supplied titles.

## Step 7: Redirect after success

After a successful POST, the controller returns a 303 redirect to the board. The browser follows it with GET. Refreshing the resulting page therefore reads the board instead of resubmitting the previous form.

Invalid input renders its errors directly so the user can fix the title. This makes the difference between success navigation and validation feedback visible in a small example.

## Verify the finished behavior

1. Add “Read the routing guide.” It should appear with a count of one.
2. Mark it complete. The text should be crossed out and the completed count should increase.
3. Remove it. The empty state should return.
4. Submit whitespace as the title. The backend should render a validation error without adding a task.
5. Open the board in a separate private browser session. It should have a separate list.
6. Inspect a form and confirm `_token` is present. Removing it before submission should yield 419.

These checks exercise the actual backend rather than only the appearance of the page.

## Common questions

**Why did my list disappear?** The session can expire, or you may have changed browser profiles or deleted the session cookie. This is temporary learning state.

**Why not use a database immediately?** The [database lesson](/learn/database) introduces a durable schema and bound queries separately. Combining every concept at the first step makes failures harder to locate.

**Why do changes use POST?** Adding, toggling, and deleting change application state. A GET link should not mutate the task list.

**Can I turn this into a real multi-user product?** Yes, but that is a new application design: add durable storage, authentication, ownership checks, authorization, and tests for cross-user access before treating it as a shared task service.

## Your next exercise

Change the heading and empty-state copy, then add a view filter for complete and incomplete tasks. Preserve the server-side validation and CSRF behavior. When something fails, follow the [debugging routine](/learn/debugging) rather than changing several layers at once.
