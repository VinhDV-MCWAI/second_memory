# 08. Tổ Chức Components & Cấu Trúc Code - Code Organization

> Cấu trúc thư mục, design patterns, naming conventions và best practices

---

## 📁 Cấu trúc Project (Monorepo)

```
second-memory/                      # Root monorepo
├── pnpm-workspace.yaml            # pnpm workspace config
├── package.json                   # Root package.json
├── docker/                        # Docker configs
├── backup/                        # Backup scripts
├── scripts/                       # Utility scripts
│
├── laravel-api/                   # Backend (Laravel)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── tests/
│   └── vendor/
│
├── nextjs-fe/                     # Frontend Dashboard (Next.js)
│   ├── src/
│   │   ├── app/                  # App Router
│   │   ├── components/           # React components
│   │   ├── lib/                  # Utils, hooks
│   │   ├── store/                # Redux store
│   │   └── styles/               # Global styles
│   └── public/
│
└── nextjs-docs/                   # Frontend Documentation (Next.js)
    ├── src/
    └── public/
```

### Monorepo vs Multi-repo?

**Chọn: Monorepo** (pnpm workspaces)

**Lý do**:
- ✅ Share dependencies (Tiptap, TailwindCSS...)
- ✅ Consistent versioning
- ✅ Easier refactoring (rename API, all consumers update)
- ✅ Single repo to clone/manage

**Trade-offs**:
- ⚠️ Larger repo size
- ⚠️ Need clear boundaries giữa projects

---

## 🏗️ Backend Structure (Laravel)

### App Directory Layout

```
laravel-api/app/
├── Console/
│   └── Kernel.php                             # Console commands
│
├── Constants/                                 # Application constants
│   ├── ApiStatus.php
│   ├── UserStatus.php
│   └── ...
│
├── Enums/                                     # PHP 8.1+ Enums
│   ├── CategoryStatus.php
│   ├── RoleType.php
│   └── ...
│
├── Events/                                    # Domain events
│   ├── CategoryCreated.php
│   ├── CategoryUpdated.php
│   └── ...
│
├── Exceptions/                                # Custom exceptions
│   ├── Handler.php
│   ├── CategoryNotFoundException.php
│   └── ...
│
├── Http/
│   ├── Controllers/                           # Controllers (thin layer)
│   │   ├── Api/
│   │   │   ├── Admin/
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── EntryController.php
│   │   │   │   └── ...
│   │   │   └── AuthController.php
│   │   └── Controller.php
│   │
│   ├── Middleware/                            # Request/Response filters
│   │   ├── Authenticate.php
│   │   ├── CheckPermission.php
│   │   └── ...
│   │
│   └── Requests/                              # Form validation
│       ├── Category/
│       │   ├── CreateCategoryRequest.php
│       │   ├── UpdateCategoryRequest.php
│       │   └── UpdateLayoutRequest.php
│       └── ...
│
├── Interfaces/                                # Contracts (Dependency Injection)
│   ├── CategoryRepositoryInterface.php
│   ├── CacheServiceInterface.php
│   └── ...
│
├── Jobs/                                      # Background jobs
│   ├── ProcessMediaUpload.php
│   ├── SendEmailNotification.php
│   └── ...
│
├── Models/                                    # Eloquent models
│   ├── Category.php
│   ├── Entry.php
│   ├── EntryDescription.php
│   ├── Admin.php
│   └── ...
│
├── Observers/                                 # Model lifecycle hooks
│   ├── CategoryObserver.php
│   ├── EntryObserver.php
│   └── ...
│
├── Providers/                                 # Service providers
│   ├── AppServiceProvider.php
│   ├── AuthServiceProvider.php
│   ├── EventServiceProvider.php
│   └── RepositoryServiceProvider.php          # DI bindings
│
├── Repositories/                              # Data access layer
│   ├── CategoryRepository.php
│   ├── EntryRepository.php
│   └── ...
│
├── Rules/                                     # Custom validation rules
│   ├── UniqueSlug.php
│   ├── ValidLayoutStructure.php
│   └── ...
│
├── Services/                                  # Business logic layer
│   ├── CategoryService.php
│   ├── EntryService.php
│   ├── MediaService.php
│   ├── SearchService.php
│   └── ...
│
├── Traits/                                    # Reusable code
│   ├── HasHistory.php
│   ├── Sluggable.php
│   └── CachesQueries.php
│
└── Utilities/                                 # Helper functions
    ├── FileHelper.php
    ├── JsonHelper.php
    └── ...
```

---

### Design Pattern: Layered Architecture

```
Request
  ↓
┌─────────────────────────────────┐
│      Controller Layer           │  ← Validate, format response
│  (Http/Controllers/*)           │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│       Service Layer             │  ← Business logic
│     (Services/*)                │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│    Repository Layer             │  ← Data access
│   (Repositories/*)              │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│       Model Layer               │  ← Eloquent ORM
│      (Models/*)                 │
└─────────────────────────────────┘
               ↓
           Database
```

**Example Flow**:

```php
// 1. Controller (thin)
class CategoryController
{
    public function __construct(private CategoryService $service) {}
    
    public function store(CreateCategoryRequest $request)
    {
        $category = $this->service->createCategory($request->validated());
        return response()->json($category, 201);
    }
}

// 2. Service (business logic)
class CategoryService
{
    public function __construct(private CategoryRepository $repo) {}
    
    public function createCategory(array $data): Category
    {
        // Business logic here
        $data['slug'] = $this->generateSlug($data['name']);
        
        $category = $this->repo->create($data);
        
        // Clear cache
        Cache::tags(['categories'])->flush();
        
        // Dispatch event
        event(new CategoryCreated($category));
        
        return $category;
    }
}

// 3. Repository (data access)
class CategoryRepository implements CategoryRepositoryInterface
{
    public function create(array $data): Category
    {
        return Category::create($data);
    }
    
    public function findBySlug(string $slug): ?Category
    {
        return Cache::remember(
            "category:slug:{$slug}",
            3600,
            fn() => Category::where('slug', $slug)->first()
        );
    }
}

// 4. Model (Eloquent)
class Category extends Model
{
    protected $table = 'category_mgmt';
    
    protected $casts = [
        'layout_structure' => 'array',  // Auto JSON decode/encode
        'is_display' => 'boolean',
    ];
}
```

---

## 🎨 Frontend Structure (Next.js)

### App Directory (Next.js 16 App Router)

```
nextjs-fe/src/
├── app/                                       # App Router (routes)
│   ├── [locale]/                             # Internationalization
│   │   ├── layout.tsx                        # Root layout
│   │   ├── page.tsx                          # Home page
│   │   ├── admin/                            # Admin routes
│   │   │   ├── layout.tsx                    # Admin layout (sidebar, auth)
│   │   │   ├── page.tsx                      # Dashboard
│   │   │   ├── categories/
│   │   │   │   ├── page.tsx                  # List categories
│   │   │   │   ├── [id]/
│   │   │   │   │   └── page.tsx              # Edit category
│   │   │   │   └── create/
│   │   │   │       └── page.tsx              # Create category
│   │   │   ├── entries/
│   │   │   ├── media/
│   │   │   └── settings/
│   │   └── not-found.tsx
│   │
│   └── api/                                   # API routes (Next.js server)
│       └── health/
│           └── route.ts
│
├── components/                                # React components
│   ├── ui/                                   # shadcn/ui components
│   │   ├── button.tsx
│   │   ├── dialog.tsx
│   │   ├── dropdown-menu.tsx
│   │   └── ...
│   │
│   ├── layouts/                              # Layout components
│   │   ├── AdminLayout.tsx
│   │   ├── Sidebar.tsx
│   │   ├── Header.tsx
│   │   └── Footer.tsx
│   │
│   ├── forms/                                # Form components
│   │   ├── FormInput.tsx
│   │   ├── FormSelect.tsx
│   │   ├── FormTextarea.tsx
│   │   └── ...
│   │
│   └── features/                             # Feature-specific components
│       ├── categories/
│       │   ├── CategoryList.tsx
│       │   ├── CategoryForm.tsx
│       │   ├── CategoryCard.tsx
│       │   └── LayoutEditor.tsx
│       ├── entries/
│       ├── media/
│       │   ├── MediaUploader.tsx
│       │   ├── MediaGallery.tsx
│       │   └── ...
│       └── editor/
│           └── TiptapEditor.tsx
│
├── lib/                                       # Utilities & configs
│   ├── api/                                  # API client
│   │   ├── axios.ts                          # Axios instance
│   │   ├── endpoints.ts                      # API URLs
│   │   └── interceptors.ts
│   │
│   ├── hooks/                                # Custom hooks
│   │   ├── useAuth.ts
│   │   ├── useDebounce.ts
│   │   ├── useLocalStorage.ts
│   │   └── ...
│   │
│   ├── utils/                                # Helper functions
│   │   ├── cn.ts                             # className merger (clsx + twMerge)
│   │   ├── formatDate.ts
│   │   ├── validators.ts
│   │   └── ...
│   │
│   ├── constants/                            # Constants
│   │   ├── routes.ts
│   │   ├── apiUrls.ts
│   │   └── ...
│   │
│   └── types/                                # TypeScript types
│       ├── api.ts
│       ├── models.ts
│       └── ...
│
├── store/                                     # Redux Toolkit store
│   ├── slices/
│   │   ├── authSlice.ts
│   │   ├── uiSlice.ts
│   │   └── ...
│   └── store.ts
│
├── styles/                                    # Global styles
│   └── globals.css
│
└── messages/                                  # i18n translations
    ├── en.json
    └── vi.json
```

---

### Component Organization Pattern

#### 1. Atomic Design (Modified)

```
components/
├── ui/              # Atoms (shadcn/ui primitives)
├── forms/           # Molecules (form fields with label + error)
├── layouts/         # Organisms (header, sidebar, full layouts)
└── features/        # Organisms (feature-specific, complex)
```

#### 2. Feature-based Organization

```
features/categories/
├── CategoryList.tsx              # List view
├── CategoryForm.tsx              # Create/Edit form
├── CategoryCard.tsx              # Single card display
├── LayoutEditor.tsx              # Complex layout editor
├── hooks/
│   └── useCategories.ts          # Data fetching hooks
└── types.ts                      # Feature-specific types
```

---

### Naming Convention

#### Files

- **Components**: PascalCase.tsx (`CategoryForm.tsx`)
- **Utilities**: camelCase.ts (`formatDate.ts`)
- **Hooks**: camelCase with `use` prefix (`useAuth.ts`)
- **Types**: PascalCase or camelCase.ts (`models.ts`, `User.ts`)
- **Pages**: kebab-case or Next.js conventions (`[id]/page.tsx`)

#### Code

**TypeScript**:
```typescript
// Interfaces: PascalCase with 'I' prefix (optional)
interface IUser {
  id: number;
  name: string;
}

// Types: PascalCase
type Category = {
  id: number;
  name: string;
};

// Enums: PascalCase
enum CategoryStatus {
  Draft = 0,
  Published = 1,
}

// Functions: camelCase
function formatDate(date: Date): string { }

// Components: PascalCase
const CategoryList: React.FC = () => { }

// Constants: UPPER_SNAKE_CASE
const API_BASE_URL = 'http://localhost:8000';

// Variables: camelCase
const categoryList = [];
```

**PHP (Laravel)**:
```php
// Classes: PascalCase
class CategoryService { }

// Methods: camelCase
public function createCategory() { }

// Properties: camelCase
private $categoryRepository;

// Constants: UPPER_SNAKE_CASE
const MAX_UPLOAD_SIZE = 10485760;  // 10MB
```

---

## 🧩 Design Patterns Được Sử Dụng

### 1. Repository Pattern (Laravel)

**Interface**:
```php
interface CategoryRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Category;
    public function create(array $data): Category;
    public function update(int $id, array $data): Category;
    public function delete(int $id): bool;
}
```

**Lợi ích**:
- Decouple business logic khỏi data access
- Dễ test (mock repository)
- Dễ swap implementation (DB → Cache → API)

---

### 2. Observer Pattern (Laravel)

```php
// app/Observers/CategoryObserver.php
class CategoryObserver
{
    public function created(Category $category)
    {
        // Log to history table
        CategoryHistory::create($category->toArray());
    }
    
    public function updated(Category $category)
    {
        // Clear cache
        Cache::forget("category:{$category->id}");
        
        // Log history
        CategoryHistory::create($category->toArray());
        
        // Broadcast event
        broadcast(new CategoryUpdated($category));
    }
}

// Register in AppServiceProvider
Category::observe(CategoryObserver::class);
```

---

### 3. Service Layer Pattern

Tách business logic ra khỏi Controller:

```php
// ❌ Bad: Logic in controller
class CategoryController
{
    public function store(Request $request)
    {
        $slug = Str::slug($request->name);
        $category = Category::create([...]);
        Cache::forget('categories');
        // ...
    }
}

// ✅ Good: Logic in service
class CategoryController
{
    public function store(CreateCategoryRequest $request)
    {
        $category = $this->categoryService->createCategory($request->validated());
        return response()->json($category);
    }
}
```

---

### 4. Factory Pattern (Laravel Testing)

```php
// database/factories/CategoryFactory.php
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug,
            'description' => $this->faker->sentence,
            'status' => $this->faker->randomElement([0, 1]),
        ];
    }
}

// Usage in tests
$category = Category::factory()->create([
    'name' => 'Test Category',
]);
```

---

### 5. Composition Pattern (React)

```typescript
// Compose complex UI from small components

// ❌ Bad: Monolithic component
const CategoryPage = () => {
  return (
    <div>
      {/* 500 lines of JSX */}
    </div>
  );
};

// ✅ Good: Composed components
const CategoryPage = () => {
  return (
    <PageLayout>
      <PageHeader title="Categories" />
      <CategoryFilters />
      <CategoryList />
      <CategoryPagination />
    </PageLayout>
  );
};
```

---

## 📦 Dependency Management

### Backend (Composer)

```json
{
  "require": {
    "laravel/framework": "^11.0",
    "php": "^8.2"
  },
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Database\\Factories\\": "database/factories/",
      "Database\\Seeders\\": "database/seeders/"
    }
  }
}
```

**Autoloading**: PSR-4 standard

---

### Frontend (pnpm)

```json
{
  "dependencies": {
    "next": "16.0.1",
    "react": "19.2.0"
  },
  "devDependencies": {
    "typescript": "^5.0",
    "@types/react": "^19.0"
  }
}
```

**Workspace** (root `pnpm-workspace.yaml`):
```yaml
packages:
  - 'nextjs-fe'
  - 'nextjs-docs'
```

**Chia sẻ dependencies**:
```bash
# Install Tiptap for all workspaces
pnpm add @tiptap/core@3.20.1 -w

# Install only for nextjs-fe
pnpm --filter nextjs-fe add axios
```

---

## 🧪 Testing Organization

### Backend Tests

```
laravel-api/tests/
├── Feature/                         # Integration tests
│   ├── Category/
│   │   ├── CreateCategoryTest.php
│   │   ├── UpdateCategoryTest.php
│   │   └── DeleteCategoryTest.php
│   └── Auth/
│       └── LoginTest.php
│
└── Unit/                            # Unit tests
    ├── Services/
    │   └── CategoryServiceTest.php
    └── Utilities/
        └── SlugHelperTest.php
```

### Frontend Tests

```
nextjs-fe/src/
├── __tests__/                       # Test files
│   ├── components/
│   │   └── CategoryList.test.tsx
│   └── lib/
│       └── utils.test.ts
│
└── __mocks__/                       # Mock files
    └── axios.ts
```

---

**Cập nhật lần cuối**: 2026-04-09
