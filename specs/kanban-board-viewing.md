# Kanban Board Viewing Feature Specification

## Overview

This document outlines the implementation of Kanban board viewing functionality for the team-based project management system. The feature allows authenticated users to view and navigate between their team's Kanban boards using a clean, intuitive interface built with Flux UI components.

## Requirements

### Functional Requirements

- **Boards Index Page**: Display all boards belonging to the user's current team
- **Grid Layout**: Show boards in a responsive grid layout (1 column mobile, 2-3 columns desktop)
- **Minimal Information**: Display only board names on index page (as requested)
- **Individual Board View**: Show detailed board view with default columns
- **Navigation Integration**: Add "Boards" links to both navbar and sidebar
- **Team Scoping**: Only show boards from user's current team
- **Authorization**: Team members can view boards, non-members cannot access

### Non-Functional Requirements

- **Authentication**: Users must be authenticated to view boards
- **Authorization**: Proper team-based access control
- **Responsive Design**: Works on mobile and desktop devices
- **User Experience**: Smooth navigation with `wire:navigate`
- **Performance**: Efficient loading with proper relationships

## Technical Implementation

### Database Schema

The implementation uses existing database schema from board creation:

- `boards` table with team_id and user_id relationships
- `columns` table with board_id and position ordering
- No additional database changes required

### Models

#### Board Model (Existing)

```php
class Board extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function columns()
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }
}
```

#### Column Model (Existing)

```php
class Column extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
```

### Authorization

#### BoardPolicy (Existing)

```php
class BoardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(User $user, Board $board): bool
    {
        return $board->team->members->contains($user) || $board->team->user->is($user);
    }
}
```

### Livewire Components

#### Component: `pages.boards.index`

- **File**: `resources/views/pages/boards/⚡index.blade.php`
- **Properties**: None (uses computed property)
- **Methods**:
    - `boards()` - Returns boards for current team, ordered by latest

```php
#[Computed]
public function boards()
{
    return Board::where('team_id', auth()->user()->currentTeam->id)
        ->latest()
        ->get();
}
```

#### Component: `pages.boards.show`

- **File**: `resources/views/pages/boards/⚡show.blade.php`
- **Properties**:
    - `board` (Board) - Route model bound board instance
- **Methods**:
    - `columns()` - Returns ordered columns for the board
    - `mount()` - Authorization and board setup

```php
public Board $board;

#[Computed]
public function columns()
{
    return $this->board->columns()->orderBy('position')->get();
}

public function mount(Board $board)
{
    $this->authorize('view', $board);
    $this->board = $board;
}
```

### Routing

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('boards', 'pages::boards.index');
    Route::livewire('boards/create', 'pages::boards.create');
    Route::livewire('boards/{board}', 'pages::boards.show');
});
```

### User Interface

#### Design Patterns

- **Flux UI Components**: Uses Flux Kanban, Cards, Buttons, and Navigation
- **Responsive Grid**: CSS Grid with responsive breakpoints
- **Consistent Styling**: Follows existing application design patterns
- **Dark Mode Support**: Inherits from existing theme system

#### Boards Index Page Layout

```blade
<div>
    <header class="mb-8 flex items-center justify-between">
        <flux:heading class="text-2xl">Boards</flux:heading>
        <flux:button href="/boards/create" variant="primary" wire:navigate>Create Board</flux:button>
    </header>

    @if ($this->boards->count() > 0)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->boards as $board)
                <flux:card href="/boards/{{ $board->id }}" wire:navigate>
                    <flux:heading class="text-lg">{{ $board->name }}</flux:heading>
                </flux:card>
            @endforeach
        </div>
    @else
        <!-- Empty state -->
    @endif
</div>
```

#### Individual Board View Layout

```blade
<div>
    <header class="mb-6">
        <flux:link href="/boards" class="inline-flex items-center gap-2 text-sm" variant="subtle" wire:navigate>
            <flux:icon.chevron-left variant="micro" />
            Boards
        </flux:link>
        <flux:heading class="mt-2 text-2xl">{{ $board->name }}</flux:heading>
        @if ($board->description)
            <flux:text class="mt-1">{{ $board->description }}</flux:text>
        @endif
    </header>

    <flux:kanban>
        @foreach ($this->columns as $column)
            <flux:kanban.column>
                <flux:kanban.column.header :heading="$column->name" :count="0" />
                <flux:kanban.column.cards>
                    <flux:text class="py-8 text-center text-zinc-500">No cards yet</flux:text>
                </flux:kanban.column.cards>
            </flux:kanban.column>
        @endforeach
    </flux:kanban>
</div>
```

#### Navigation Integration

**Navbar (Desktop)**:

```blade
<flux:navbar class="-mb-px max-lg:hidden">
    <flux:navbar.item href="/boards" wire:navigate>Boards</flux:navbar.item>
</flux:navbar>
```

**Sidebar (Mobile + Desktop)**:

```blade
<flux:sidebar.nav>
    <flux:button href="/boards" variant="ghost" align="start" wire:navigate>Boards</flux:button>
</flux:sidebar.nav>
```

## User Flow

### Boards Index Flow

1. **Access**: User clicks "Boards" in navbar/sidebar or navigates to `/boards`
2. **Authorization**: System checks user has current team
3. **Data Loading**: Boards for current team are loaded with relationships
4. **Display**: Grid of board cards is shown
5. **Empty State**: If no boards, helpful message with "Create Board" button
6. **Navigation**: User clicks board card to view individual board

### Individual Board Flow

1. **Access**: User clicks board card or navigates directly to `/boards/{id}`
2. **Authorization**: System checks user can view the board
3. **Data Loading**: Board with columns is loaded
4. **Display**: Board header with Flux Kanban component
5. **Empty State**: Columns show "No cards yet" message
6. **Navigation**: User can navigate back to boards list

## Error Handling

### Authorization Errors

- **No Current Team**: Redirect to dashboard
- **Non-Member Access**: Return 403 Forbidden
- **Unauthenticated**: Redirect to login

### Edge Cases

- **Empty Board List**: Show helpful empty state
- **Board Without Columns**: Graceful handling (shouldn't occur with default columns)
- **Multiple Teams**: Only shows boards from current team

## Testing Strategy

### Test Coverage

#### Boards Index Tests

- **Happy Path**: View boards with existing boards
- **Empty State**: View when no boards exist
- **Team Scoping**: Only shows current team boards
- **Authorization**: Requires authentication and current team

#### Individual Board Tests

- **Happy Path**: View board with columns
- **Authorization**: Team members can access, non-members cannot
- **Data Display**: Shows board name, description, and columns
- **Empty State**: Shows "No cards yet" in columns

### Test Implementation

```php
// Example test structure
test('it can view boards index page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->current_team_id = $team->id;
    $user->save();

    $board = Board::factory()->create(['team_id' => $team->id]);

    Livewire::actingAs($user)
        ->test('pages::boards.index')
        ->assertSee('Boards')
        ->assertSee($board->name);
});
```

## Performance Considerations

### Database Optimization

- **Efficient Queries**: Uses `where()` with team_id for filtering
- **Relationship Loading**: Proper eager loading to prevent N+1 queries
- **Indexing**: Foreign keys properly indexed in migrations

### Frontend Optimization

- **Livewire Navigation**: Uses `wire:navigate` for smooth transitions
- **Component Efficiency**: Computed properties for memoization
- **Minimal JavaScript**: Relies on Livewire's efficient DOM updates

## Future Considerations

### Potential Enhancements

- **Board Search**: Search/filter functionality on index page
- **Board Management**: Edit/delete/archive boards
- **Column Management**: Add/remove/reorder columns
- **Board Statistics**: Show card counts, activity metrics
- **Board Templates**: Predefined board structures

### Scalability

- **Pagination**: For teams with many boards
- **Caching**: Board lists and individual board data
- **Real-time Updates**: WebSocket integration for collaboration

## Dependencies

### Laravel Packages

- **Livewire** (v2.9.2) - Component framework
- **Flux UI** - Component library for Kanban and UI elements
- **Laravel Cashier** - Billing integration (existing)

### External Services

- **Database** - MySQL/PostgreSQL for data persistence
- **Authentication** - Laravel's built-in auth system
- **Team Management** - Existing team system

## Security Considerations

### Authorization

- **Team-Based Access**: Only team members can view boards
- **Policy-Based Permissions**: Uses Laravel's authorization system
- **Route Model Binding**: Secure board retrieval

### Input Validation

- **Route Parameters**: Proper validation of board IDs
- **User Input**: All user inputs properly validated
- **XSS Protection**: Laravel's built-in XSS protection

## Deployment Notes

### Migration Requirements

- No new migrations required (uses existing schema)
- Clear application cache after deployment
- Test navigation and authorization

### Configuration

- No additional configuration required
- Uses existing team and user systems
- Follows established application patterns

## Files Created/Modified

### New Files

1. `resources/views/pages/boards/⚡index.blade.php` - Boards listing page
2. `resources/views/pages/boards/⚡show.blade.php` - Individual board view
3. `database/factories/BoardFactory.php` - Factory for testing
4. `tests/Feature/Boards/ViewBoardTest.php` - Comprehensive test suite

### Modified Files

1. `routes/web.php` - Added boards index route
2. `resources/views/layouts/app.blade.php` - Added navigation links

## Integration with Cards System

The board viewing implementation is designed to seamlessly integrate with the future cards system:

- **Flux Kanban Component**: Already supports cards and drag-and-drop
- **Column Structure**: Default columns ready for card population
- **Empty States**: Clear indication where cards will appear
- **Performance**: Efficient data loading for card-heavy boards

When cards are implemented, the existing `flux:kanban.column.cards` sections can be populated with card data without changing the overall structure.
