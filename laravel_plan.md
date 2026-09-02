# Pizza Store App — Build Instructions (Sections 2–7)

Prerequisite: Laravel must be installed first (`composer create-project laravel/laravel .`).
Everything below assumes that has been run and `php artisan serve` works.

---

## 2. Core Domain Model

We'll build four models: `Pizza`, `Topping`, `Order`, `OrderItem`.

### 2.1 Generate models with migrations, factories, and (where useful) a seeder

```bash
php artisan make:model Pizza -mf
php artisan make:model Topping -mf
php artisan make:model Order -mf
php artisan make:model OrderItem -m
php artisan make:seeder PizzaSeeder
```

- `-m` generates a migration file alongside the model.
- `-f` generates a factory (used for fake data in tests/seeding).

### 2.2 Relationships to define

In `app/Models/Pizza.php`:

```php
class Pizza extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'image'];

    public function toppings(): BelongsToMany
    {
        return $this->belongsToMany(Topping::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

In `app/Models/Topping.php`:

```php
class Topping extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price'];

    public function pizzas(): BelongsToMany
    {
        return $this->belongsToMany(Pizza::class);
    }
}
```

In `app/Models/Order.php`:

```php
class Order extends Model
{
    use HasFactory;

    protected $fillable = ['customer_name', 'customer_email', 'status', 'total'];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

In `app/Models/OrderItem.php`:

```php
class OrderItem extends Model
{
    protected $fillable = ['order_id', 'pizza_id', 'quantity', 'unit_price'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function pizza(): BelongsTo
    {
        return $this->belongsTo(Pizza::class);
    }
}
```

**Concepts learned:** Eloquent models, `$fillable` (mass-assignment protection), `hasMany`/`belongsTo`/`belongsToMany` relationship methods, and why relationships are just query builders under the hood (`$pizza->toppings` vs `$pizza->toppings()`).

---

## 3. Database

### 3.1 Migrations

Edit each generated migration in `database/migrations/`.

**`create_pizzas_table`:**

```php
Schema::create('pizzas', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 6, 2);
    $table->string('image')->nullable();
    $table->timestamps();
});
```

**`create_toppings_table`:**

```php
Schema::create('toppings', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->decimal('price', 6, 2)->default(0);
    $table->timestamps();
});
```

**Pivot table** for the `belongsToMany` (Laravel expects `pizza_topping`, alphabetical singular names):

```bash
php artisan make:migration create_pizza_topping_table
```

```php
Schema::create('pizza_topping', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pizza_id')->constrained()->cascadeOnDelete();
    $table->foreignId('topping_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
});
```

**`create_orders_table`:**

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('customer_name');
    $table->string('customer_email');
    $table->string('status')->default('pending'); // pending, baking, delivered
    $table->decimal('total', 8, 2)->default(0);
    $table->timestamps();
});
```

**`create_order_items_table`:**

```php
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('pizza_id')->constrained();
    $table->unsignedInteger('quantity')->default(1);
    $table->decimal('unit_price', 6, 2);
    $table->timestamps();
});
```

Run migrations:

```bash
php artisan migrate
```

**Concepts learned:** schema builder syntax, foreign keys/constraints, cascading deletes, decimal vs integer column types.

### 3.2 Factories

`database/factories/PizzaFactory.php`:

```php
public function definition(): array
{
    return [
        'name' => fake()->randomElement(['Margherita', 'Pepperoni', 'BBQ Chicken', 'Veggie Supreme', 'Hawaiian']),
        'description' => fake()->sentence(),
        'price' => fake()->randomFloat(2, 8, 20),
        'image' => null,
    ];
}
```

`database/factories/ToppingFactory.php`:

```php
public function definition(): array
{
    return [
        'name' => fake()->randomElement(['Mushrooms', 'Extra Cheese', 'Olives', 'Jalapenos', 'Onions']),
        'price' => fake()->randomFloat(2, 0.5, 2.5),
    ];
}
```

### 3.3 Seeder

`database/seeders/PizzaSeeder.php`:

```php
public function run(): void
{
    $toppings = Topping::factory(6)->create();

    Pizza::factory(6)->create()->each(function (Pizza $pizza) use ($toppings) {
        $pizza->toppings()->attach(
            $toppings->random(rand(1, 3))->pluck('id')
        );
    });
}
```

Register it in `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call(PizzaSeeder::class);
}
```

Run it:

```bash
php artisan migrate:fresh --seed
```

**Concepts learned:** factories vs seeders (factories define *how* to fake one record; seeders decide *how many* and *when* to run), `attach()` for pivot tables, `migrate:fresh` (drops all tables and re-runs migrations).

---

## 4. Routes & Controllers

### 4.1 Generate controllers

```bash
php artisan make:controller PizzaController
php artisan make:controller CartController
php artisan make:controller OrderController
```

### 4.2 Routes (`routes/web.php`)

```php
use App\Http\Controllers\PizzaController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;

Route::get('/', [PizzaController::class, 'index'])->name('pizzas.index');
Route::get('/pizzas/{pizza}', [PizzaController::class, 'show'])->name('pizzas.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{pizza}', [CartController::class, 'add'])->name('cart.add');
Route::delete('/cart/remove/{pizza}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/checkout', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
```

### 4.3 Controller logic

`PizzaController`:

```php
public function index()
{
    $pizzas = Pizza::with('toppings')->get();
    return view('pizzas.index', compact('pizzas'));
}

public function show(Pizza $pizza)
{
    $pizza->load('toppings');
    return view('pizzas.show', compact('pizza'));
}
```

Note `Pizza $pizza` — this is **route model binding**: Laravel automatically fetches the `Pizza` by its `{pizza}` route ID (404s if not found).

`CartController` — use the session as a simple cart store (id => quantity):

```php
public function index()
{
    $cart = session('cart', []);
    $pizzas = Pizza::whereIn('id', array_keys($cart))->get();
    return view('cart.index', compact('pizzas', 'cart'));
}

public function add(Pizza $pizza)
{
    $cart = session('cart', []);
    $cart[$pizza->id] = ($cart[$pizza->id] ?? 0) + 1;
    session(['cart' => $cart]);

    return back()->with('status', "{$pizza->name} added to cart.");
}

public function remove(Pizza $pizza)
{
    $cart = session('cart', []);
    unset($cart[$pizza->id]);
    session(['cart' => $cart]);

    return back()->with('status', 'Removed from cart.');
}
```

`OrderController`:

```php
public function create()
{
    $cart = session('cart', []);
    $pizzas = Pizza::whereIn('id', array_keys($cart))->get();
    return view('orders.create', compact('pizzas', 'cart'));
}

public function store(StoreOrderRequest $request)
{
    $cart = session('cart', []);
    abort_if(empty($cart), 400, 'Cart is empty.');

    $pizzas = Pizza::whereIn('id', array_keys($cart))->get();

    $order = Order::create([
        'customer_name' => $request->customer_name,
        'customer_email' => $request->customer_email,
        'total' => $pizzas->sum(fn ($p) => $p->price * $cart[$p->id]),
    ]);

    foreach ($pizzas as $pizza) {
        $order->items()->create([
            'pizza_id' => $pizza->id,
            'quantity' => $cart[$pizza->id],
            'unit_price' => $pizza->price,
        ]);
    }

    session()->forget('cart');

    return redirect()->route('orders.show', $order)->with('status', 'Order placed!');
}

public function show(Order $order)
{
    $order->load('items.pizza');
    return view('orders.show', compact('order'));
}
```

**Concepts learned:** resource-style routing with named routes, route model binding, session helper, form requests (next section), redirect/flash messaging (`->with('status', ...)`).

---

## 5. Views (Blade)

### 5.1 Layout

`resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Pizza Store</title>
    @vite('resources/css/app.css')
</head>
<body>
    <nav>
        <a href="{{ route('pizzas.index') }}">Menu</a>
        <a href="{{ route('cart.index') }}">Cart</a>
    </nav>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <main>
        @yield('content')
    </main>
</body>
</html>
```

### 5.2 Menu page

`resources/views/pizzas/index.blade.php`:

```blade
@extends('layouts.app')

@section('content')
    <h1>Our Pizzas</h1>
    <div class="grid">
        @foreach ($pizzas as $pizza)
            <div class="card">
                <h2><a href="{{ route('pizzas.show', $pizza) }}">{{ $pizza->name }}</a></h2>
                <p>{{ $pizza->description }}</p>
                <p>${{ number_format($pizza->price, 2) }}</p>
                <form method="POST" action="{{ route('cart.add', $pizza) }}">
                    @csrf
                    <button type="submit">Add to cart</button>
                </form>
            </div>
        @endforeach
    </div>
@endsection
```

### 5.3 Pizza detail

`resources/views/pizzas/show.blade.php` — similar, plus lists `$pizza->toppings`.

### 5.4 Cart page

`resources/views/cart/index.blade.php` — table of pizzas with quantity from `$cart[$pizza->id]`, a remove button per row, and a link to checkout.

### 5.5 Checkout form

`resources/views/orders/create.blade.php`:

```blade
@extends('layouts.app')

@section('content')
    <h1>Checkout</h1>

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('orders.store') }}">
        @csrf
        <label>Name <input type="text" name="customer_name" value="{{ old('customer_name') }}"></label>
        <label>Email <input type="email" name="customer_email" value="{{ old('customer_email') }}"></label>
        <button type="submit">Place order</button>
    </form>
@endsection
```

### 5.6 Order confirmation

`resources/views/orders/show.blade.php` — display `$order->items` with pizza name, quantity, unit price, and `$order->total`.

**Concepts learned:** `@extends`/`@section`/`@yield` layout inheritance, `@csrf` (required on every POST/PUT/DELETE form), `$errors` bag, `old()` for repopulating input after a failed submit, Blade loops/conditionals.

---

## 6. Validation & Form Handling

### 6.1 Form Request

```bash
php artisan make:request StoreOrderRequest
```

`app/Http/Requests/StoreOrderRequest.php`:

```php
public function authorize(): bool
{
    return true; // no auth system yet — anyone can place an order
}

public function rules(): array
{
    return [
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_email' => ['required', 'email', 'max:255'],
    ];
}
```

Using `StoreOrderRequest $request` as the type-hint in `OrderController::store()` (already shown above) makes Laravel run validation *before* the controller method body executes. On failure it automatically redirects back with input and errors — that's what populates `$errors` and `old()` in the Blade view.

### 6.2 Why this matters

- Keeps controllers thin — validation logic lives in its own class.
- `authorize()` is a hook for policy checks later (e.g., only logged-in users can order) — return `false` and Laravel throws a 403.
- Validation rules are declarative and reusable; can add rules like `min:2`, `regex:`, custom rule classes, etc.

**Concepts learned:** Form Requests, validation rule syntax, automatic redirect-back-with-errors behavior, `old()` helper.

---

## 7. Testing

Decide Pest vs PHPUnit first — Laravel ships PHPUnit by default; Pest is layered on top and reads more like plain English. Examples below in **Pest** syntax (install via `composer require pestphp/pest --dev --with-all-dependencies && php artisan pest:install` if not already scaffolded), with PHPUnit equivalents noted.

### 7.1 Test database setup

In `phpunit.xml`, confirm (or add) an in-memory SQLite env block so tests never touch your dev database:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### 7.2 Feature test: menu page

```bash
php artisan make:test PizzaMenuTest
```

```php
use App\Models\Pizza;

it('shows all seeded pizzas on the menu', function () {
    Pizza::factory()->count(3)->create();

    $response = $this->get(route('pizzas.index'));

    $response->assertOk();
    Pizza::all()->each(fn ($pizza) => $response->assertSee($pizza->name));
});
```

PHPUnit equivalent uses `use RefreshDatabase;` trait on the test class and a `public function test_...()` method with the same body via `$this->get(...)`.

### 7.3 Feature test: add to cart

```php
it('adds a pizza to the session cart', function () {
    $pizza = Pizza::factory()->create();

    $this->post(route('cart.add', $pizza));

    $this->get(route('cart.index'))
        ->assertOk()
        ->assertSee($pizza->name);
});
```

### 7.4 Feature test: place a valid order

```php
use App\Models\Order;

it('creates an order from cart contents', function () {
    $pizza = Pizza::factory()->create(['price' => 10]);
    $this->post(route('cart.add', $pizza));

    $response = $this->post(route('orders.store'), [
        'customer_name' => 'Jason',
        'customer_email' => 'jason@example.com',
    ]);

    $order = Order::first();
    $response->assertRedirect(route('orders.show', $order));
    expect($order->items)->toHaveCount(1);
    expect($order->total)->toEqual(10);
});
```

### 7.5 Feature test: validation failure

```php
it('rejects an order with missing customer name', function () {
    $pizza = Pizza::factory()->create();
    $this->post(route('cart.add', $pizza));

    $response = $this->post(route('orders.store'), [
        'customer_email' => 'jason@example.com',
    ]);

    $response->assertSessionHasErrors('customer_name');
    $this->assertDatabaseCount('orders', 0);
});
```

### 7.6 Running tests

```bash
php artisan test
# or, with Pest installed:
./vendor/bin/pest
```

Every test class (or Pest file via `uses(RefreshDatabase::class)` in `tests/Pest.php`) should use the `RefreshDatabase` trait so migrations run fresh for each test — no leftover state between tests.

**Concepts learned:** feature tests hitting real HTTP routes, `RefreshDatabase` for test isolation, `assertOk`/`assertSee`/`assertRedirect`/`assertSessionHasErrors`/`assertDatabaseCount`, factories used to arrange test data, the difference between a *unit* test (tests one class/method in isolation) and a *feature* test (tests a full HTTP request/response cycle) — this plan uses feature tests since they best validate user-facing behavior.

---

## Recap of what each section teaches

| Section | Core Laravel concepts |
|---|---|
| 2. Domain model | Eloquent models, relationships, mass assignment |
| 3. Database | Migrations, schema builder, factories, seeders |
| 4. Routes & Controllers | Routing, route model binding, session, redirects |
| 5. Views | Blade templating, layouts, forms, CSRF |
| 6. Validation | Form Requests, validation rules, error bag |
| 7. Testing | Feature tests, RefreshDatabase, HTTP test assertions |
