## IMPORTANT

- Always use the `artisan make:*` command when creating models, migrations, factories, controllers, and other Laravel files. This ensures proper boilerplate, registration, and maintainability.

- Page Components: Use `pages::` namespace for full-page components: `php artisan make:livewire pages::auth.login`. Creates component at `resources/views/pages/auth/⚡login.blade.php`. Route with `Route::livewire('/login', 'pages::auth.login')`. Access route parameters via `mount($id)` method or automatic property binding.

- Regular Components: Use simple names: `php artisan make:livewire organizations.create`. Creates component at `resources/views/components/organizations/⚡create.blade.php`. Render with `<livewire:organizations.create />`.

- Multi-file Components: Add `--mfc` flag: `php artisan make:livewire organizations.create --mfc`. Creates directory structure with separate PHP, Blade, JS, and test files. Better for large, complex components.

- Test files for Livewire components and pages are co-located with their respective files in `resources/views/`.

- Use Pest PHP testing framework with `it()` function.

- Component naming: use simple names for Livewire components (`'organizations.create'`) and dot notation for pages (`'pages::auth.login'`).

- Common testing patterns:
  - `Livewire::test('component-name')` for component testing
  - `actingAs($user)` or `Livewire::actingAs($user)` for authentication
  - `assertHasNoErrors()`, `assertRedirect()`, `assertForbidden()` for assertions
  - `get()`, `post()` for HTTP request testing
  - `Notification::fake()` for notification testing
