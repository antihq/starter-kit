# Kanban Board Creation Feature Specification

## Overview

This document outlines the implementation of the Kanban board creation feature for the team-based project management system. The feature allows authenticated users to create team-scoped Kanban boards with default columns.

## Requirements

### Functional Requirements

- **Team-Scoped Boards**: Boards can only be created within the context of a team
- **No Personal Boards**: Users cannot create personal boards outside of team context
- **Default Columns**: Each board automatically creates three default columns:
    - "Maybe" (position: 1)
    - "Not Now" (position: 2)
    - "Done" (position: 3)
- **Simple Team Access**: All team members can view and edit boards
- **No Board Limits**: No restrictions on the number of boards per team
- **No Task Assignment**: Task assignment is not implemented during board creation

### Non-Functional Requirements

- **Authentication**: Users must be authenticated to create boards
- **Authorization**: Users must belong to a team to create boards
- **Validation**: Input validation for board name and description
- **User Experience**: Clean, simple interface following existing design patterns

## Technical Implementation

### Database Schema

#### Boards Table

```sql
CREATE TABLE boards (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    team_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### Columns Table

```sql
CREATE TABLE columns (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    board_id BIGINT NOT NULL,
    position INT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Models

#### Board Model

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

#### Column Model

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

#### BoardPolicy

```php
class BoardPolicy
{
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(User $user, Board $board): bool
    {
        return $board->team->members->contains($user) || $board->team->user->is($user);
    }

    public function update(User $user, Board $board): bool
    {
        return $board->team->members->contains($user) || $board->team->user->is($user);
    }
}
```

### Livewire Component

#### Component: `pages.boards.create`

- **File**: `resources/views/pages/boards/⚡create.blade.php`
- **Properties**:
    - `name` (string) - Board name (required)
    - `description` (string) - Board description (optional)
- **Methods**:
    - `create()` - Handles board creation with validation and default column creation

#### Form Validation Rules

```php
[
    'name' => 'required|string|max:255',
    'description' => 'nullable|string|max:1000',
]
```

#### Creation Logic

1. Validate input data
2. Check user has current team
3. Create board with user's current team
4. Create default columns with predefined names and positions
5. Redirect to newly created board

### Routing

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('boards/create', 'pages.boards.create');
    Route::livewire('boards/{board}', 'pages.boards.show');
});
```

### User Interface

#### Design Patterns

- Follows existing Flux UI component patterns
- Uses `max-w-[512px]` container for centered layout
- Consistent with team creation page design
- Back navigation to dashboard with `wire:navigate`

#### Form Elements

- Board Name (required text input)
- Description (optional textarea)
- Create button (primary variant, zinc color)

## User Flow

1. **Access**: Authenticated user navigates to `/boards/create`
2. **Authorization**: System checks user has current team
3. **Form Display**: Board creation form is displayed
4. **Input**: User enters board name and optional description
5. **Submission**: User submits form
6. **Validation**: System validates input
7. **Creation**: Board and default columns are created
8. **Redirect**: User is redirected to `/boards/{id}`

## Error Handling

### Validation Errors

- Empty board name
- Board name exceeding 255 characters
- Description exceeding 1000 characters

### Authorization Errors

- User without current team redirected to dashboard
- Unauthenticated users redirected to login

### Edge Cases

- User belongs to multiple teams (uses current team)
- Team member permissions (all members can access)

## Card Creation System (Implemented)

### Overview

The board creation system has been extended with a complete card creation functionality, allowing users to create and manage cards within board columns.

### Database Schema Enhancement

#### Cards Table

```sql
CREATE TABLE cards (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    column_id BIGINT NOT NULL,
    position INT NOT NULL DEFAULT 0,
    user_id BIGINT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Card Model

```php
class Card extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function column()
    {
        return $this->belongsTo(Column::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function board()
    {
        return $this->column->board;
    }
}
```

### Enhanced Column Model

```php
class Column extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class)->orderBy('position');
    }
}
```

### Card Creation Features

#### Dedicated Creation Page

- **Route**: `/boards/{board}/cards/create?column={id}`
- **RESTful Design**: Nested resource pattern showing cards belong to boards
- **Fallback**: Uses "Maybe" column when no column specified
- **Form**: Title (required) + Rich text description (optional)
- **Editor**: Flux Editor with simplified toolbar (`bold italic | bullet | link`)
- **Redirect**: Returns to board after creation

#### Card Viewing

- **Route**: `/cards/{card}`
- **Display**: Title and rich text description
- **Navigation**: Breadcrumb with board name
- **HTML Rendering**: Safe display of rich content

#### Board Integration

- **Card Display**: Title-only cards in columns
- **Positioning**: New cards added to top (position 0)
- **Navigation**: "Add Card" buttons link to creation page
- **Responsive**: Works on mobile and desktop

### Authorization

#### CardPolicy

```php
class CardPolicy
{
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(User $user, Card $card): bool
    {
        return $card->board->team->members->contains($user) || $card->board->team->user->is($user);
    }

    public function update(User $user, Card $card): bool
    {
        return $card->board->team->members->contains($user) || $card->board->team->user->is($user);
    }
}
```

### Testing Coverage

#### Card Tests

- **CreateCardTest.php**: 4 comprehensive tests
- **Creation**: Form submission and database storage
- **Viewing**: Individual card display with rich content
- **Validation**: Title requirements and character limits
- **Authorization**: Team-based access control

### Current Implementation Status

- ✅ **Complete Card System**: Creation, viewing, and board integration
- ✅ **Rich Text Support**: Flux Editor with HTML storage
- ✅ **Position Management**: Integer-based for future drag-and-drop
- ✅ **Full Test Coverage**: 41/41 tests passing
- ✅ **Clean Architecture**: Dedicated pages vs modals

## Future Considerations

### Potential Enhancements

- Card editing functionality
- Card deletion with confirmations
- Drag & drop card movement between columns
- Column management (add/remove/reorder)
- Board templates
- Board colors/themes
- Advanced permissions
- Board archiving
- Activity logging

### Scalability

- Database indexing for performance
- Caching strategies for large teams
- Pagination for board lists

## Dependencies

### Laravel Packages

- Livewire (v2.9.2) - Component framework
- Flux UI - Component library
- Laravel Cashier - Billing integration

### External Services

- Database (MySQL/PostgreSQL)
- Authentication system
- Team management system

## Security Considerations

### Input Validation

- Server-side validation for all inputs
- XSS protection through Laravel's built-in features
- SQL injection prevention through Eloquent ORM

### Authorization

- Team-based access control
- User authentication required
- Policy-based permissions

## Performance Considerations

### Database Optimization

- Proper indexing on foreign keys
- Efficient queries with relationships
- Minimal N+1 query problems

### Frontend Optimization

- Livewire's efficient DOM updates
- Minimal JavaScript overhead
- Optimized asset loading

## Deployment Notes

### Migration Order

1. Run `boards` table migration
2. Run `columns` table migration
3. Clear application cache
4. Test board creation functionality

### Configuration

- No additional configuration required
- Uses existing team and user systems
- Follows existing application patterns
