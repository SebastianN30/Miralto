<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v3
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>

# Miralto – Restaurante Campestre

## Descripción del proyecto

Sistema de gestión interna para el restaurante campestre **Miralto** (Colombia). Permite registrar y administrar órdenes de clientes, productos del menú y usuarios del sistema. El diseño visual sigue una estética rural/familiar colombiana.

**Stack:**
- Backend: Laravel 13 + PHP 8.5 + Fortify (autenticación)
- Frontend: Vue 3 + Inertia.js v3 + TypeScript
- Estilos: Tailwind CSS v4 con paleta personalizada
- Build: Vite con `@laravel/vite-plugin-wayfinder` (genera tipos TS desde rutas Laravel)
- Tests: PHPUnit 12

---

## Ejecutar PHP

Siempre usar `/c/laragon/bin/php/php-8.5.1/php.exe` para comandos artisan. La versión 8.1 del PATH del sistema no es compatible con las dependencias del proyecto.

```bash
/c/laragon/bin/php/php-8.5.1/php.exe artisan migrate
/c/laragon/bin/php/php-8.5.1/php.exe artisan test --compact
vendor/bin/pint --dirty --format agent  # formatear PHP modificado
```

---

## Paleta de colores

Definida en `resources/css/app.css` con `@theme inline`. Usar siempre los tokens Tailwind:

| Token Tailwind | CSS var | RGB | Uso |
|---|---|---|---|
| `bg-miralto-verde` / `text-miralto-verde` | `--color-miralto-verde` | (45, 85, 45) | Acciones primarias, botones CTA, badges activos |
| `bg-miralto-beige` / `text-miralto-beige` | `--color-miralto-beige` | (240, 235, 210) | Fondo suave de página, secciones agrupadas |
| `bg-miralto-marron` / `text-miralto-marron` | `--color-miralto-marron` | (120, 70, 45) | Acentos, separadores, pago dividido |
| `bg-miralto-blanco` / `text-miralto-blanco` | `--color-miralto-blanco` | (250, 245, 235) | Cards, áreas de respiro |

---

## Wayfinder – generación de rutas TypeScript

Las rutas y acciones de controladores se generan automáticamente en:
- `resources/js/actions/App/Http/Controllers/` → acciones de controladores (CRUD)
- `resources/js/routes/` → rutas nombradas

Importar siempre desde estas ubicaciones. Nunca hardcodear URLs.

```ts
import * as OrderController from '@/actions/App/Http/Controllers/OrderController';
import { index, create, show } from '@/routes/orders';

// Uso
OrderController.store.url()
OrderController.update.url({ order: 1 })
index()   // /orders
show({ order: 1 })  // /orders/1
```

---

## Estructura de base de datos

### `users`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `name` | string | |
| `email` | string unique | para empleados con acceso sin correo real se usa `{username}@miralto.local` (placeholder) |
| `username` | string(50) nullable unique | login alternativo; requerido cuando el empleado tiene acceso al sistema |
| `password` | hashed | |
| `role` | string(20) | `admin`, `employee`, `waiter`, `cook` — default: employee |
| `is_active` | boolean | default: true — inactivo no puede iniciar sesión |
| `two_factor_*` | columns | Fortify 2FA |
| `remember_token` | string | |
| `timestamps` | | |

**Modelo `User`:** `isAdmin()`, `isEmployee()`, `isWaiter()`, `isCook()`, relación `orders()` hasMany.
**`homePath()`:** ruta de aterrizaje tras login según `role` — `waiter` → `/waiter`, `cook` → `/kitchen`, resto → `/dashboard`. Usado por `LoginResponse` (ver login más abajo) y por el middleware de zona para redirigir cuando alguien intenta salir de su zona.
**Factory states:** `admin()`, `inactive()`, `withTwoFactor()`

---

### `categories`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `name` | string | |
| `description` | text nullable | |
| `is_active` | boolean | default: true |
| `timestamps` | | |

**Modelo `Category`:** relaciones `products()` hasMany, `activeProducts()` hasMany (filtrada por `is_active=true`).

---

### `products`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `category_id` | FK nullable | → categories, nullOnDelete |
| `name` | string | |
| `description` | text nullable | |
| `price` | decimal(10,2) | precio de venta |
| `cost` | decimal(10,2) nullable | costo de producción |
| `stock` | integer nullable | |
| `is_active` | boolean | default: true |
| `timestamps` | | |
| `deleted_at` | softDeletes | |

**Modelo `Product`:** `marginPercentage()` calcula `(price - cost) / price * 100`. `ingredientCost()` suma `cost_per_unit × quantity` de todos los insumos vinculados. Cuando se guardan insumos, el campo `cost` se recalcula automáticamente. Relaciones `category()` belongsTo, `orderItems()` hasMany, `ingredients()` belongsToMany (pivot: `ProductIngredient`, columna extra: `quantity`). El campo `printer_id` (FK nullable → `printers`) determina a qué impresora se envía el producto en el módulo del mesero (futuro). Índices en `category_id` e `is_active`.

---

### `printers`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `name` | string | nombre de la impresora |
| `description` | string nullable | |
| `ip_address` | string nullable | IP en la red local |
| `is_active` | boolean | default: true |
| `timestamps` | | |

**Modelo `Printer`:** `scopeActive()`, relación `products()` hasMany. Los productos la referencian con `printer_id` nullable — cuando sea null, se imprime/agrupa en "Impresora por defecto". Usada como filtro de estación en la vista de cocina (`/kitchen`).

---

### `ingredients`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `name` | string | nombre del insumo |
| `unit` | string(50) | unidad de medida (kg, g, litros, ml, unidad, porción, taza, cucharada, cucharadita) |
| `cost_per_unit` | decimal(10,4) | costo por unidad de medida |
| `is_active` | boolean | default: true |
| `timestamps` | | |

**Modelo `Ingredient`:** relación `products()` belongsToMany.
**Factory:** `inactive()` state.

---

### `product_ingredients` (pivot)
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `product_id` | FK | → products, cascadeOnDelete |
| `ingredient_id` | FK | → ingredients, restrictOnDelete |
| `quantity` | decimal(10,3) | cantidad del insumo en el producto |
| `timestamps` | | |
| unique | (product_id, ingredient_id) | |

**Modelo `ProductIngredient`:** extiende `Pivot` (no `Model`), `$incrementing = true`.

---

### `cash_registers` (caja del día)
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `user_id` | FK | → users, restrictOnDelete (quien abrió la caja) |
| `opened_at` | dateTime | momento de apertura |
| `closed_at` | dateTime nullable | momento de cierre |
| `opening_amount` | decimal(12,2) | efectivo inicial |
| `closing_amount` | decimal(12,2) nullable | efectivo contado al cierre |
| `opening_notes` | text nullable | |
| `closing_notes` | text nullable | |
| `status` | enum(open, closed) | default: open |
| `timestamps` | | |
| índice | (status, opened_at) | |

**Modelo `CashRegister`:**
- `expectedCash()` → `opening_amount + cashIn − cashOut` (esperado en caja en este momento)
- `difference()` → `closing_amount − expectedCash()` (sólo después de cerrar)
- `totalCashIn()` / `totalCashOut()` → suma de movimientos en efectivo
- `totalSales()`, `totalIncome()`, `totalExpense()`, `totalRefund()`
- `isOpen()` / `isClosed()`
- Scope: `open()` filtra `status = 'open'`
- Relaciones: `user()`, `movements()` hasMany, `orders()` hasMany

---

### `cash_movements`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `cash_register_id` | FK | → cash_registers, cascadeOnDelete |
| `user_id` | FK nullable | → users, nullOnDelete |
| `order_id` | FK nullable | → orders, nullOnDelete (sólo en ventas/devoluciones) |
| `type` | enum(income, expense, sale, refund) | |
| `payment_method` | enum(cash, transfer, card) nullable | |
| `amount` | decimal(12,2) | |
| `description` | string | |
| `timestamps` | | |
| índices | (cash_register_id, type), (order_id) | |

**Tipos de movimiento:**
- `income` / `expense` → manuales (creados por usuario desde la UI)
- `sale` / `refund` → automáticos (creados por `OrderObserver` cuando una orden se paga o se cancela)

**Modelo `CashMovement`:** `isManual()` retorna `true` si es income/expense; `isAutomatic()` para sale/refund (los automáticos no se pueden eliminar manualmente).

---

### `order_logs` (auditoría de órdenes)
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `order_id` | FK | → orders, cascadeOnDelete |
| `user_id` | FK nullable | → users, nullOnDelete |
| `action` | string | created / status_changed / payment_updated / deleted |
| `description` | string | texto legible |
| `changes` | JSON nullable | `{field: {from: x, to: y}}` |
| `timestamps` | | |
| índice | (order_id, created_at) | |

Generado automáticamente por el `OrderObserver` (PHP attribute `#[ObservedBy]` sobre el modelo `Order`).

---

### `employees`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `name` | string(100) unique | |
| `position` | string(100) nullable | cargo (texto libre) |
| `role` | string(20) | `waiter` (Mesero), `cook` (Cocinero), `other` (Otro) — default: other |
| `user_id` | FK nullable unique | → users, nullOnDelete — solo se llena si el empleado tiene acceso al sistema |
| `is_active` | boolean | default: true |
| `timestamps` | | |
| índice | is_active | |

**Modelo `Employee`:** `ROLES` (const, para selects), `LOGIN_ROLES = ['waiter', 'cook']` (únicos roles que pueden tener usuario/clave). `scopeActive()`, relaciones `user()` belongsTo, `orders()` hasMany.

**Acceso al sistema (opcional por empleado):** en `EmployeeController::store()/update()`, si el rol es `waiter`/`cook` y se activa el toggle "Tiene acceso al sistema", se exige `username`+`password` y se crea/sincroniza un `User` (`EmployeeController::syncUser()`) con `role` igual al del empleado y `email` placeholder `{username}@miralto.local` (solo se re-deriva del username si el email actual sigue siendo el placeholder, nunca sobreescribe un correo real). Si se desactiva el toggle, el `User` vinculado se marca `is_active = false` (no se borra). Al eliminar un empleado con usuario vinculado, se borra también el `User` en la misma transacción — bloqueado si el empleado o su usuario ya registraron órdenes (pedir desactivar en vez de eliminar).

---

### `orders`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `user_id` | FK | → users, restrictOnDelete (quien registró el pedido) |
| `cash_register_id` | FK nullable | → cash_registers, nullOnDelete (caja activa al crearse) |
| `table_id` | FK nullable | → tables, nullOnDelete (mesa física seleccionada de la lista) |
| `table_name` | string(100) nullable | nombre/número de mesa: texto libre ("P1", "C2") o copiado del nombre de `table_id` |
| `employee_id` | FK nullable | → employees, nullOnDelete — la orden es de consumo de un empleado |
| `total` | decimal(10,2) | calculado desde items (incluye cargo por servicio e impuesto si aplican) |
| `status` | enum(pending, paid, cancelled) | default: pending |
| `payment_method` | enum(cash, transfer, card) nullable | método principal |
| `payment_amount_1` | decimal(10,2) nullable | monto método principal (pago dividido) |
| `payment_method_2` | enum(cash, transfer, card) nullable | segundo método (pago dividido) |
| `payment_amount_2` | decimal(10,2) nullable | monto segundo método |
| `notes` | text nullable | observaciones |
| `service_charge` | boolean | default: false — si se aplica cargo por servicio |
| `service_charge_percentage` | decimal(5,2) nullable | % del cargo por servicio (editable; si es null se asume 10 en `computedServiceChargeAmount()`/`recalculateTotal()`) |
| `service_charge_amount` | decimal(10,2) nullable | monto calculado y persistido del cargo por servicio |
| `tax` | boolean | default: false — si se aplica el impuesto fijo (`Order::TAX_PERCENTAGE = 3.5`) |
| `tax_amount` | decimal(10,2) nullable | monto calculado y persistido del impuesto |
| `timestamps` | | |

**Modelo `Order`:**
- `recalculateTotal()` → suma `subtotal` de los items; si `service_charge = true` añade `service_charge_amount` (según `service_charge_percentage`, default 10%); si `tax = true` añade `tax_amount` (`TAX_PERCENTAGE` fijo 3.5%) — ambos independientes, calculados sobre el mismo subtotal
- `computedServiceChargeAmount()` / `computedTaxAmount()` → calculan el monto sin guardarlo (para previews en UI)
- `isPending()` / `isPaid()` / `isCancelled()` → helpers sobre `status`
- `hasSplitPayment()` → `payment_method_2 !== null`
- `totalPaid()` → suma `payment_amount_1 + payment_amount_2`
- `remainingBalance()` → diferencia entre total y lo pagado
- Relaciones: `user()` belongsTo, `cashRegister()` belongsTo, `table()` belongsTo, `employee()` belongsTo, `items()` hasMany, `logs()` hasMany (latest), `cashMovements()` hasMany.
- Atributo `#[ObservedBy([OrderObserver::class])]` registra el observer (audit log + auto cash movements + descuento de stock).
- Índices en `user_id`, `cash_register_id`, `status`, `created_at`.

---

### `order_items`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `order_id` | FK | → orders, cascadeOnDelete |
| `product_id` | FK | → products, restrictOnDelete |
| `quantity` | unsignedInteger | |
| `price` | decimal(10,2) | **snapshot** del precio al momento de venta |
| `subtotal` | decimal(10,2) | `price * quantity` |
| `notes` | string(500) nullable | nota del mesero por ítem (ej. "sin cebolla") |
| `prepared_at` | timestamp nullable | marcado por cocina (`/kitchen`) cuando el ítem está listo |
| `timestamps` | | |

**Modelo `OrderItem`:** relaciones `order()` belongsTo, `product()` belongsTo. Índices en `order_id` y `product_id`.

---

### `wallets`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `name` | string | nombre de la billetera |
| `type` | enum(nubank, daviplata, nequi, other) | tipo de billetera digital |
| `account_identifier` | string nullable | número de cuenta o identificador |
| `initial_balance` | decimal(12,2) | saldo inicial de la billetera |
| `is_active` | boolean | default: true |
| `notes` | text nullable | observaciones |
| `timestamps` | | |
| índices | type, is_active | |

**Modelo `Wallet`:**
- `scopeActive()` → filtra billeteras activas
- `totalInbound()` → suma de transacciones tipo `payment` + `income`
- `totalOutbound()` → suma de transacciones tipo `expense`
- `currentBalance()` → `initial_balance + totalInbound() - totalOutbound()`
- `totalIncome()` → solo ingresos manuales (tipo `income`)
- `totalPayments()` → solo pagos recibidos (tipo `payment`)
- `totalExpenses()` → solo egresos (tipo `expense`)
- Relaciones: `transactions()` hasMany.

---

### `tables` (mesas físicas)
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `name` | string | nombre/número de la mesa |
| `capacity` | unsignedInteger nullable | número de personas |
| `zone` | string(100) nullable | zona/sección del restaurante |
| `is_active` | boolean | default: true |
| `timestamps` | | |
| índice | is_active | |

**Modelo `Table`:** `scopeActive()`, relación `orders()` hasMany. Selector de chips en `orders/Create`, `orders/Edit` y `waiter/Create` (con fallback de texto libre a `orders.table_name`, ver tabla `orders`).

---

### `suppliers`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `name` | string | |
| `contact_name` | string(150) nullable | persona de contacto |
| `phone` | string(50) nullable | |
| `email` | string nullable | |
| `notes` | text nullable | |
| `is_active` | boolean | default: true |
| `timestamps` | | |
| índice | is_active | |

**Modelo `Supplier`:** `scopeActive()`. Sin relaciones a otras tablas todavía (catálogo informativo).

---

### `wallet_transactions`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigIncrements | PK |
| `wallet_id` | FK | → wallets, cascadeOnDelete |
| `user_id` | FK nullable | → users, nullOnDelete |
| `order_id` | FK nullable | → orders, nullOnDelete (vincula pago a una orden) |
| `type` | enum(income, expense, payment) | income=ingreso manual, expense=egreso, payment=pago recibido |
| `amount` | decimal(12,2) | monto |
| `description` | string | descripción del movimiento |
| `reference` | string nullable | referencia externa (ej: REF-123456) |
| `transaction_date` | date | fecha del movimiento |
| `timestamps` | | |
| índices | [wallet_id, type], transaction_date | |

**Modelo `WalletTransaction`:**
- `isInbound()` → `true` si tipo es `income` o `payment`
- `isOutbound()` → `true` si tipo es `expense`
- Relaciones: `wallet()` belongsTo, `user()` belongsTo, `order()` belongsTo (opcional).

---

## Módulo Mesero – `/waiter`

Vista enfocada para meseros. **Layout dedicado** (`resources/js/layouts/WaiterLayout.vue`) — sin el sidebar admin, con tab bar fija abajo (Pedidos / Nuevo). Registrado en `app.ts` con la regla `name.startsWith('waiter/') → WaiterLayout`.

### Permisos / scope
- Solo expone pedidos. No hay acceso a productos, caja, dashboard ni configuración desde la vista del mesero.
- Restringido por rol vía middleware de zona (ver sección de Login/Zonas más abajo): un usuario `role=waiter` **solo** puede estar en `/waiter`; admin/employee acceden igual que siempre.
- Desde el sidebar admin existe el link "Modo mesero" (icono `ConciergeBell`) que lleva a `/waiter`.

### Comportamiento clave
- **Crear pedido** (`/waiter/create`): formulario tipo POS con campo de **mesa** (texto libre, requerido), selector de productos por categoría, carrito sticky arriba. Cada ítem del carrito permite agregar una nota individual (ej. "sin cebolla", "término medio"). El pedido se guarda con `status = 'pending'`.
- **Agregar productos** (`/waiter/{order}/items`, POST): el mesero **solo puede AGREGAR productos**, nunca quitar ni reducir. Si se equivoca, debe cancelarse el pedido completo desde administración.
  - El backend valida `status = 'pending'` antes de aceptar nuevos ítems.
  - Recalcula el total después de insertar.
- **Ver pedido** (`/waiter/{order}`): los ítems se agrupan por `printer_id` del producto (cocina/bar/barra) en preparación para el módulo de impresión. Cuando `printer_id = null`, se agrupan bajo "Impresora por defecto".

### Notas de pedido vs notas de ítem
- **Nota de pedido** (`orders.notes`): observación general (ej. "para llevar").
- **Nota de ítem** (`order_items.notes`): específica del producto (ej. "sin sal").

### Layout / UX
- Mobile-first: tab bar inferior fija con dos accesos (Pedidos / Nuevo pedido), header con logo y logout.
- Botones grandes (`h-12`), grid de productos 2-3 columnas, feedback táctil con `active:scale-95`.
- El carrito en `Create.vue` es sticky bajo el header para que sea siempre visible.

---

## Login y zonas por rol (Mesero / Cocinero)

### Registro público deshabilitado
`Features::registration()` está comentado en `config/fortify.php` (Fortify solo registra las rutas `/register` si el feature está activo). Todos los usuarios se crean desde `/employees` (roles con acceso) o los seeders — nunca por auto-registro. El link "Regístrate" de `Login.vue` es condicional a `canRegister` (prop calculada desde el mismo feature flag), así que desaparece solo.

### Login con usuario o correo
`Fortify::authenticateUsing()` (en `FortifyServiceProvider`) busca el usuario por `email` **o** `username` (case-insensitive), valida `is_active` y la contraseña. El input del formulario (`resources/js/pages/auth/Login.vue`) es un campo de texto único ("Usuario o correo"), no un `type="email"`.

### Redirección post-login por rol
`App\Http\Responses\LoginResponse` (implementa `Laravel\Fortify\Contracts\LoginResponse`, registrado en `FortifyServiceProvider::register()`) redirige a `$user->homePath()` en vez de siempre a `/dashboard`. La ruta `/` también usa `homePath()`.

### Middleware de zona (`App\Http\Middleware\EnsureZoneAccess`, alias `zone`)
`routes/web.php` está dividido en 3 grupos: `zone:panel` (admin/employee), `zone:waiter`, `zone:kitchen`. El middleware redirige (no 403) a `homePath()` si el rol del usuario no debería estar en esa zona:
- `waiter`/`cook` → bloqueados del panel admin, cada uno bloqueado de la zona del otro.
- `admin`/`employee` → acceso normal a todo (incluye `/waiter` y `/kitchen` para supervisar).

### Empleados con acceso — creación de usuario
Ver sección `employees` arriba (tabla + modelo). La UI de `/employees` (`resources/js/pages/employees/Index.vue`) tiene un select de Rol y, solo si el rol es Mesero/Cocinero, un panel de "Tiene acceso al sistema" con usuario/clave.

---

## Módulo Cocina – `/kitchen`

Vista de cola para el rol `cook`. **Layout dedicado** (`resources/js/layouts/KitchenLayout.vue`) — header simple con logo/nombre/logout, sin tab bar inferior (no hay "crear" en cocina). Registrado en `app.ts` con `name.startsWith('kitchen/') → KitchenLayout`. Acceso también desde el sidebar admin (icono `ChefHat`, "Cocina").

### `KitchenController`
- `index()`: pedidos `status=pending` con al menos un item sin `prepared_at`, con `user`/`items.product` cargados. Pasa también `stations` (impresoras activas) para el filtro.
- `toggleItem(OrderItem $item)` (`PATCH /kitchen/items/{item}`): alterna `prepared_at` (null ↔ now) de un item suelto. Rechaza si la orden ya no está `pending`.
- `markOrder(Order $order)` (`PATCH /kitchen/orders/{order}`): marca `prepared_at = now()` en todos los items sin preparar de la orden; si se manda `printer_id`, solo los de esa estación (permite "todo listo" por estación cuando hay un filtro activo).

### `kitchen/Index.vue`
- `usePoll(15000, { only: ['orders'] })` — refresca la cola sola cada 15s.
- Filtro por estación (chips, basado en `printers`); `null` = todas.
- Cada pedido es una tarjeta: mesa/#pedido, mesero, badge de minutos de espera (color según antigüedad: gris <10min, ámbar ≥10min, rojo ≥20min), checklist de items (click para alternar listo/pendiente, con nota del item en ámbar si tiene), botón "Todo listo".
- Las tarjetas cuyo(s) item(s) de la estación filtrada ya están todos listos desaparecen de la vista (pero la orden sigue viva si tiene items de otra estación pendientes).

---

## Módulo Dashboard – `/dashboard`

Controlador: `DashboardController` (single-action, `__invoke`). Renderiza `pages/Dashboard.vue` con cuatro bloques de información:

### KPIs (top row)
- `revenue_today` — ventas de hoy (status=paid)
- `revenue_yesterday` — para calcular `change_vs_yesterday` (% diferencia)
- `revenue_week` — ventas acumuladas desde el lunes
- `orders_today` — total de órdenes del día (cualquier estado)
- `avg_ticket` — promedio histórico de `total` en órdenes pagadas

### Ventas de los últimos 14 días
Array completo de 14 días (incluyendo días con `total = 0`) → renderizado como bar chart con `chart.js` + `vue-chartjs`. Permite ver tendencia y "huecos".

### Proyección mensual
- `current_revenue` — acumulado del mes actual
- `daily_average` — `current_revenue / días_transcurridos`
- `projection` — `daily_average × días_del_mes` (cierre estimado)
- `last_month_revenue` + `vs_last_month_pct` — comparación con mes anterior
- Renderizado como line chart con dos series: **Real** (sólido, hasta hoy) y **Proyección** (dashed, días futuros)

### Top y Bottom productos
- **Top:** join de `order_items` + `orders` filtrando `status=paid`, agrupado por producto, ordenado por `SUM(quantity) DESC`. Renderizado con barras horizontales de progreso (max-width = el más vendido).
- **Bottom:** **left join** desde `products` para incluir productos con cero ventas. Filtrado a `is_active=true` y `deleted_at IS NULL`. Útil para detectar productos de bajo desempeño (los `0 unidades` se resaltan en rojo).

### Stack visual
- `chart.js` v4 + `vue-chartjs` v5 (instalados con `npm install chart.js vue-chartjs`).
- Componentes registrados en el script setup: `BarElement`, `CategoryScale`, `LinearScale`, `LineElement`, `PointElement`, `Filler`, `Title`, `Tooltip`, `Legend`.
- Colores tomados directos de la paleta Miralto: `rgb(45, 85, 45)` (verde) para datos reales, `rgb(120, 70, 45)` (marrón) para proyección.

---

## Módulo de Caja – comportamiento clave

### Flujo de la caja del día
1. Usuario abre la caja desde `/cash` con un monto de apertura → se crea `CashRegister` con `status = 'open'`.
2. Mientras está abierta:
   - Las **órdenes nuevas** se asocian automáticamente al `cash_register_id` de la sesión activa (`OrderController::store()`).
   - Cuando una orden cambia a `paid` → el `OrderObserver` crea movimientos `sale` (uno o dos según pago dividido).
   - Cuando una orden pagada se cancela → se crea un movimiento `refund` que revierte la venta.
   - El usuario puede registrar **ingresos** o **egresos** manuales (compra de gas, propinas, etc.).
3. Al cerrar la caja, el usuario declara cuánto efectivo cuenta físicamente. El sistema calcula la **diferencia** vs el efectivo esperado.
4. Solo puede haber **una caja abierta a la vez** (validado en `CashRegisterController::store()`).

### Cálculos clave (en el modelo `CashRegister`)
- **Efectivo esperado:** `opening_amount + (ventas/ingresos en efectivo) − (egresos/devoluciones en efectivo)`
- **Diferencia:** `closing_amount − expectedCash()` — se evalúa solo al cerrar
- **Regla de negocio:** la caja física **solo refleja efectivo**. Las ventas en `transfer`/`card` se muestran en una card aparte ("Ventas por otros métodos") y NO entran en el cálculo de efectivo esperado.
- Métodos del modelo:
  - `totalSalesCash()` — solo ventas en efectivo (lo que entra a la caja)
  - `totalSalesOther()` — transferencia + tarjeta sumadas (informativo)
  - `salesByPaymentMethod()` — desglose `{cash, transfer, card}`
  - `totalSales()` — gran total (sumando todos los métodos, mantenido para reportes)

### Permisos
- **Eliminar movimientos manuales:** solo admin. No se pueden eliminar movimientos automáticos (`sale`, `refund`) ni movimientos de cajas cerradas.
- **Cerrar caja:** cualquier usuario autenticado.

---

## Módulo de Auditoría de Órdenes (Observer)

`App\Observers\OrderObserver` (registrado vía `#[ObservedBy]` en `Order`). Captura:

| Evento | Acción |
|---|---|
| `created` | Crea `OrderLog` con action=`created` |
| `updated` con cambio de `status` | Log `status_changed` con `{from, to}` en `changes` |
| `updated` con cambio de pago (sin cambio de status) | Log `payment_updated` con campos modificados |
| `deleting` | Log `deleted` |

**Gotcha:** el logging de borrado usa el evento **`deleting`** (no `deleted`) porque `order_logs.order_id` tiene `cascadeOnDelete()` — crear el `OrderLog` después del DELETE físico (evento `deleted`) viola el FK y lanza `SQLSTATE[23000]` 1452.

**Side effects en cambios de status:**
- `→ paid`: crea `CashMovement` tipo `sale` (uno o dos en pago dividido) en la caja activa, y descuenta stock (`adjustStock($order, -1)`).
- `paid → cancelled`: crea `CashMovement` tipo `refund` por el total, y repone stock (`adjustStock($order, 1)`).

Si no hay caja abierta cuando se debería crear un movimiento automático, el observer simplemente lo omite (no falla).

**Descuento automático de stock (`adjustStock()`):** por cada `OrderItem` de la orden, hace `Product::whereKey($id)->whereNotNull('stock')->decrement/increment('stock', $qty)` — SQL atómico, sin condición de carrera. Solo afecta productos con `stock` no nulo (trackeados); se permite quedar en negativo, sin bloquear ni lanzar excepción. Solo se dispara en la transición de `status`, nunca al solo agregar ítems a una orden `pending`. `OrderController::split()` no requiere ajuste — solo mueve `OrderItem` entre órdenes, nunca toca `Product::stock` directamente.

---

## Módulo de Billeteras – comportamiento clave

Gestión de billeteras digitales (Nequi, Daviplata, Nubank, otras) con registro de movimientos.

### Tipos de billetera
- `nubank` (morado), `nequi` (rosa), `daviplata` (naranja), `other` (gris) — cada tipo tiene colores distintivos en la UI.

### Saldo calculado
- `currentBalance() = initial_balance + totalInbound() - totalOutbound()`
- La UI muestra el saldo en rojo si es negativo, verde si positivo.

### Tipos de transacción
- `payment` — pago recibido de un cliente (puede vincularse a una `order_id`)
- `income` — ingreso manual (ej. recarga externa)
- `expense` — egreso (ej. retiro, transferencia saliente)

### Reglas de negocio
- Solo se pueden registrar movimientos en billeteras con `is_active = true`.
- Solo admins pueden eliminar transacciones.
- No hay transacciones automáticas vinculadas al observer de órdenes — los pagos en billetera se registran manualmente.

### UI (wallets/Show.vue)
- Filtro por tipo con botones (Todos / Pago recibido / Ingreso / Egreso).
- Iconos por tipo: `ArrowDownLeft` (income), `CircleDollarSign` (payment), `ArrowUpRight` (expense).
- Columnas de tabla responsivas (algunas se ocultan en pantallas pequeñas).
- Modal "Registrar movimiento": tipo, monto, descripción, referencia (opcional), fecha (default hoy).
- Modal "Editar billetera": todos los campos incluyendo `is_active` (checkbox) e `initial_balance`.

---

## Módulo de Productos – comportamiento clave

### CRUD completo
- **Index:** filtros por búsqueda, categoría y estado (active/inactive/deleted). Muestra precio, costo, margen% y stock.
- **Create/Edit:** formulario en dos columnas — izquierda (info básica + insumos), derecha (estado/stock + precios).
- **Archivar (destroy):** soft delete — el producto sigue en la BD con `deleted_at`. Solo admins ven el botón.
- **Restaurar (restore):** `POST /products/{id}/restore` — restaura productos archivados. Solo admins.

### Constructor de insumos (ingredient builder)
- El formulario recibe todos los insumos (`ingredients` prop) del servidor.
- El usuario selecciona un insumo del dropdown → aparece una fila con campo de cantidad editable.
- Cada fila muestra: nombre | unidad | cantidad | costo unitario × cantidad = subtotal.
- El **costo total** se calcula reactivamente (`ingredientCost = sum(cost_per_unit × quantity)`).
- Al guardar, `ProductController::syncIngredients()` hace `sync()` en la pivot y guarda `cost = ingredientCost()` en el producto.

### Precio sugerido
- Solo se muestra cuando hay insumos en el formulario.
- Fórmula: `suggestedPrice = ingredientCost / (1 - margin / 100)`
- El margen se ajusta con un slider (10%–90%, default 65%).
- Botón "Aplicar precio sugerido" redondea al siguiente centenar y lo copia al campo de precio.

### Nuevo insumo inline
- Botón "Nuevo insumo" abre un `Dialog` (Reka UI) con campos: nombre, unidad, costo.
- Se envía a `POST /ingredients` con `preserveState: true` para no perder el estado del formulario.
- La respuesta incluye `flash.newIngredient` con el nuevo insumo; un `watch` en el componente lo auto-agrega a la lista de insumos del formulario.

### Control de acceso
- **Archivar/Restaurar:** solo `role = admin`. Backend: comprobación implícita en el controller. Frontend: `v-if="isAdmin"`.

---

## Módulo de Órdenes – comportamiento clave

### Pago dividido
Permite que una orden se pague con dos métodos distintos (ej. parte en efectivo, parte en transferencia).
- `payment_method` + `payment_amount_1` = primer método
- `payment_method_2` + `payment_amount_2` = segundo método
- `payment_amount_2` se calcula como `total - payment_amount_1` en el frontend

### Cargo por servicio (`service_charge`) e impuesto (`tax`)
Dos toggles independientes en el formulario de edición (`orders/Edit.vue`), calculados sobre el mismo subtotal de items:
- **Cargo por servicio:** porcentaje configurable (`service_charge_percentage`, default 10% si es null). Al activarlo: `service_charge_amount = subtotal × pct/100`.
- **Impuesto:** porcentaje fijo `Order::TAX_PERCENTAGE = 3.5`. Al activarlo: `tax_amount = subtotal × 0.035`.
- Al desactivar cualquiera: su campo `*_amount` vuelve a `null` y deja de sumarse al total.
- `recalculateTotal()` respeta ambos flags de forma independiente — `total = subtotal + (service_charge_amount si aplica) + (tax_amount si aplica)`.

### Confirmación de método de pago (`orders/Show.vue`)
El botón "Cobrar $X" no dispara el cobro directo. Muestra un sub-paso (`payConfirmStep` ref) dentro del mismo `DialogContent` (sin anidar un segundo `Dialog`, por foco/z-index): panel de confirmación con el método elegido, billetera si es `transfer`, y el total — botones "Volver" / "Sí, cobrar $X" (recién ahí llama a la ruta de pago). `watch(payDialogOpen)` resetea `payConfirmStep` al cerrar el diálogo. Motivado por errores reales de personal seleccionando mal el método de pago. **No** aplica a `Create.vue`/`Edit.vue` (flujo de pago dividido aparte).

### Recibo imprimible (`orders/Show.vue`)
Botón "Imprimir" → `window.print()`. Patrón CSS: contenido normal de la página con `print:hidden`, y un bloque hermano `hidden print:block` con el recibo ticket-style (encabezado MIRALTO, items, subtotal, servicio/impuesto si aplican, total, método de pago). **Distinto** del "módulo de impresión real" (pendiente en roadmap) — eso es envío a impresoras térmicas/red por `printer_id` para cocina/barra; esto es solo impresión de recibo vía diálogo del navegador.

### Agregar items a orden existente (`/orders/{order}/items`)
Permite añadir productos a una orden ya guardada que siga en estado `pending`.
- Solo se pueden agregar items, nunca reducir ni eliminar (la eliminación requiere cancelar la orden).
- El backend valida `status = 'pending'` antes de aceptar.
- Llama `recalculateTotal()` al final, respetando `service_charge` si ya estaba aplicado.
- Disponible desde el sidebar del admin (no exclusivo del módulo mesero).

### Dividir orden (`/orders/{order}/split`)
Mueve items (o parte de ellos) a una nueva orden pendiente.
- Si la cantidad a separar iguala la cantidad total del item → el item se mueve completo
- Si la cantidad es menor → se reduce la cantidad original y se crea un nuevo item en la nueva orden
- Ambas órdenes llaman `recalculateTotal()` al final (respetan `service_charge` individual de cada una)

### Control de acceso
- **Eliminar orden:** solo usuarios con `role = 'admin'`. Backend: `abort(403)` si no es admin. Frontend: `v-if="isAdmin"` con `usePage().props.auth.user.role`.
- El campo `role` está excluido del `#[Hidden]` del modelo, por lo que llega en las props de Inertia.

---

## Rutas web (`routes/web.php`)

```
GET    /                             → redirect → dashboard si auth, login si no
GET    /dashboard                    → DashboardController (auth, verified, single-action __invoke)

GET    /orders                       → OrderController@index
GET    /orders/create                → OrderController@create
POST   /orders                       → OrderController@store
GET    /orders/{order}               → OrderController@show
GET    /orders/{order}/edit          → OrderController@edit
PATCH  /orders/{order}               → OrderController@update
DELETE /orders/{order}               → OrderController@destroy (admin only)
POST   /orders/{order}/split         → OrderController@split

GET    /products                     → ProductController@index
GET    /products/create              → ProductController@create
POST   /products                     → ProductController@store
GET    /products/{product}/edit      → ProductController@edit
PATCH  /products/{product}           → ProductController@update
DELETE /products/{product}           → ProductController@destroy (soft delete, admin only)
POST   /products/{id}/restore        → ProductController@restore (admin only)

POST   /ingredients                  → IngredientController@store
PATCH  /ingredients/{ingredient}     → IngredientController@update
DELETE /ingredients/{ingredient}     → IngredientController@destroy (desactiva)

GET    /cash                         → CashRegisterController@index
POST   /cash                         → CashRegisterController@store (abrir caja)
GET    /cash/{cash}                  → CashRegisterController@show
PATCH  /cash/{cash}/close            → CashRegisterController@close
POST   /cash/{cash}/movements        → CashMovementController@store
DELETE /cash/movements/{movement}    → CashMovementController@destroy (admin only, manual movs)

GET    /waiter                       → WaiterController@index (lista de pedidos del día) — zone:waiter
GET    /waiter/create                → WaiterController@create
POST   /waiter                       → WaiterController@store
GET    /waiter/{order}               → WaiterController@show (items agrupados por printer_id)
POST   /waiter/{order}/items         → WaiterController@addItems (solo agregar, nunca quitar)

GET    /kitchen                      → KitchenController@index (cola de pendientes, agrupable por estación) — zone:kitchen
PATCH  /kitchen/items/{item}         → KitchenController@toggleItem
PATCH  /kitchen/orders/{order}       → KitchenController@markOrder (opcional: filtra por printer_id)

POST   /orders/{order}/items         → OrderController@addItems (agregar items desde admin, solo pending)

GET    /wallets                      → WalletController@index (listado + stats globales)
POST   /wallets                      → WalletController@store (crear billetera)
GET    /wallets/{wallet}             → WalletController@show (detalle + transacciones)
PATCH  /wallets/{wallet}             → WalletController@update (editar billetera)
POST   /wallets/{wallet}/transactions → WalletTransactionController@store (registrar movimiento)
DELETE /wallets/transactions/{transaction} → WalletTransactionController@destroy (admin only)

GET    /tables                        → TableController@index (lista con pedidos activos count)
POST   /tables                        → TableController@store (admin only)
PATCH  /tables/{table}                → TableController@update (admin only)
DELETE /tables/{table}                → TableController@destroy (admin only, si no tiene pedidos pendientes)

GET    /employees                     → EmployeeController@index (lista con órdenes count)
POST   /employees                     → EmployeeController@store (admin only)
PATCH  /employees/{employee}          → EmployeeController@update (admin only)
DELETE /employees/{employee}          → EmployeeController@destroy (admin only, bloqueado si tiene órdenes → desactivar)

GET    /suppliers                     → SupplierController@index (lista con stats)
POST   /suppliers                     → SupplierController@store (admin only)
PATCH  /suppliers/{supplier}          → SupplierController@update (admin only)
DELETE /suppliers/{supplier}          → SupplierController@destroy (admin only)
```

**Settings** (en `routes/settings.php`): profile, security (password + 2FA), appearance.

---

## Frontend – estructura Vue

### Páginas (`resources/js/pages/`)
```
auth/           Login, Register, ForgotPassword, ResetPassword, ConfirmPassword, VerifyEmail, TwoFactorChallenge
orders/
  Index.vue     Lista con estadísticas (total, pendientes, pagadas, ingresos), filtros y paginación
  Create.vue    Selector de productos por categoría + carrito + checkout con pago dividido
  Show.vue      Detalle de orden, acciones rápidas (marcar pagado, cancelar), diálogo dividir orden, diálogo agregar items
  Edit.vue      Formulario edición de estado y pago (incluye pago dividido + toggle cargo por servicio)
wallets/
  Index.vue     Grid de billeteras con stats globales (total, activas, saldo total), modal crear billetera
  Show.vue      Detalle con 4 cards (saldo actual, pagos, ingresos, egresos), filtro por tipo, tabla de transacciones, modales registrar movimiento y editar billetera
tables/
  Index.vue     CRUD de mesas: stats (total/activas/inactivas), chip-selector de mesas con zona, diálogos crear/editar (nombre, capacidad, zona, toggle activo), guard si tiene pedidos pendientes
employees/
  Index.vue     CRUD de empleados (nombre, cargo, rol, activo). Rol Mesero/Cocinero puede tener acceso al sistema (usuario+clave, panel condicional). Se asocian a órdenes vía selector en Create/Edit; filtro "Solo de empleados"/por empleado en orders/Index
kitchen/
  Index.vue     Cola de pedidos pendientes agrupable por estación (impresora), checklist de items, "Todo listo"
suppliers/
  Index.vue     CRUD de proveedores: stats, tabla con contacto/teléfono/correo clicables, diálogos crear/editar, toggle activo
products/
  Index.vue     Lista con filtros (búsqueda, categoría, estado), stats (total/activos/inactivos/archivados), tabla con margen%
  Create.vue    Formulario con constructor de insumos + panel de precio sugerido
  Edit.vue      Igual que Create pero pre-cargado con datos del producto
cash/
  Index.vue     Historial de cajas + tarjeta de caja activa + Dialog para abrir nueva caja
  Show.vue      Detalle de caja: stats (apertura/ventas/ingresos/egresos), resumen de efectivo, lista de movimientos con filtro, órdenes asociadas, Dialogs para nuevo movimiento y cerrar caja
waiter/
  Index.vue     Lista de pedidos del día (pendientes + pagados), botón "Nuevo"
  Create.vue    POS mobile-first: mesa + categorías + grid productos + carrito sticky con notas por ítem
  Show.vue      Detalle del pedido con items agrupados por impresora + Dialog para agregar más productos
settings/       Profile, Security, Appearance
Dashboard.vue   KPIs + bar chart 14 días + line chart proyección + top/bottom productos (chart.js)
Welcome.vue
```

### Layouts
Asignación automática por nombre de página en `resources/js/app.ts`:
- `Welcome` → sin layout
- `auth/*` → `AuthLayout` (split panel con branding Miralto)
- `waiter/*` → `WaiterLayout` (mobile-first, tab bar inferior, sin sidebar admin)
- `kitchen/*` → `KitchenLayout` (header simple, sin sidebar admin, sin tab bar)
- `settings/*` → `AppLayout` + `SettingsLayout`
- todo lo demás → `AppLayout` (sidebar admin + AppSidebarHeader con breadcrumbs)

Los breadcrumbs se configuran en cada página con:
```ts
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Órdenes', href: index() },
            { title: 'Detalle de orden', href: '#' },
        ],
    },
});
```
**Restricción:** `defineOptions` se hoist fuera del `setup()`, por lo que no puede referenciar `props`. Los títulos dinámicos (ej. `#${order.id}`) deben ir en `<Head title>` y en el `h1` de la página.

### Tipos TypeScript (`resources/js/types/`)
- `models.ts` → `Category`, `Ingredient`, `ProductIngredient`, `Product`, `OrderItem`, `Order`, `OrderStatus`, `PaymentMethod`, `OrderLog`, `OrderLogAction`, `CashRegister`, `CashRegisterStatus`, `CashMovement`, `CashMovementType`, `Wallet`, `WalletType`, `WalletTransaction`, `WalletTransactionType`, `PaginatedData<T>`
  - `Order` incluye `table_id`, `service_charge: boolean` + `service_charge_percentage`/`service_charge_amount` (string | null), `tax: boolean` + `tax_amount: string | null`
  - `WalletType`: `'nubank' | 'daviplata' | 'nequi' | 'other'`
  - `WalletTransactionType`: `'income' | 'expense' | 'payment'`
  - `Wallet` incluye props computadas opcionales: `current_balance`, `total_inbound`, `total_outbound`
- `auth.ts` → `User` (incluye `username: string | null`, `role: 'admin' | 'employee' | 'waiter' | 'cook'`, `is_active: boolean`)
- `models.ts` también incluye `Employee` (`role: EmployeeRole`, `user_id`, `user?: {id, username, is_active}`) y `OrderItem.prepared_at: string | null`
- `navigation.ts` → `BreadcrumbItem`, `NavItem`

### Componentes UI (`resources/js/components/ui/`)
Librería headless basada en Reka UI. Disponibles: `alert`, `avatar`, `badge`, `breadcrumb`, `button`, `card`, `checkbox`, `collapsible`, `dialog`, `dropdown-menu`, `input`, `input-otp`, `label`, `navigation-menu`, `select`, `separator`, `sheet`, `spinner`, `tabs`.

---

## Seeders de datos de prueba

```bash
/c/laragon/bin/php/php-8.5.1/php.exe artisan db:seed
```

| Seeder | Datos |
|---|---|
| `UsersSeeder` | admin@miralto.com (admin), empleado@miralto.com (employee), maria@miralto.com (employee) — todos con password `password` |
| `EmployeesSeeder` | Empleados demo con acceso: `mesero` / Juan Mesero (role=waiter) y `cocina` / Ana Cocinera (role=cook), password `password`; más `Pedro Auxiliar` (role=other, sin acceso) |
| `CategoriesSeeder` | Entradas, Platos Fuertes, Sopas y Caldos, Parrilla, Bebidas, Postres |
| `ProductsSeeder` | 21 productos colombianos campestres (Bandeja Paisa, Trucha, Patacones, etc.) |
| `OrdersSeeder` | 20 órdenes de prueba con 1–5 items aleatorios por orden |

---

## Tests

| Archivo | Cubre |
|---|---|
| `tests/Feature/MiraltoModelsTest.php` | 15 tests: creación de modelos, relaciones Eloquent, soft deletes, `marginPercentage()`, `recalculateTotal()`, factory states de usuarios |
| `tests/Feature/WalletModuleTest.php` | Módulo de billeteras: CRUD, saldo calculado, transacciones, restricciones de acceso |
| `tests/Feature/StockDeductionTest.php` | 7 tests: descuento/reposición de stock del Observer en los 6 escenarios de cambio de status, productos no trackeados |
| `tests/Feature/DailySalesTest.php` / `DashboardTest.php` | KPIs del dashboard, ventas por día, proyección mensual |
| `tests/Feature/CategoryTest.php` | CRUD de categorías |
| `tests/Feature/TableTest.php` | CRUD de mesas físicas, guard si tiene pedidos pendientes |
| `tests/Feature/SupplierTest.php` | CRUD de proveedores |
| `tests/Feature/EmployeeTest.php` | CRUD de empleados, asociación a órdenes, filtro por empleado en Orders/Index |
| `tests/Feature/StaffAccessTest.php` | Login por usuario/email, redirección por rol, middleware de zona (waiter/cook/admin/employee), CRUD de acceso de empleados, cola de cocina |
| `tests/Feature/Auth/*` | Suite estándar de Fortify (login, registro —se salta, ya deshabilitado—, reset de password, 2FA, verificación de email) |
| `tests/Feature/Settings/*` | Actualización de perfil, seguridad (password + 2FA) |
| `tests/Feature/ExampleTest.php` | ⚠️ Falla preexistente y no relacionada: espera `GET /` en 200, pero `/` siempre ha redirigido (login o `homePath()`). No corregir sin que Sebastian lo pida. |

```bash
/c/laragon/bin/php/php-8.5.1/php.exe artisan test --compact tests/Feature/MiraltoModelsTest.php
/c/laragon/bin/php/php-8.5.1/php.exe artisan test --compact  # todos los tests
```

---

## Roadmap – fases futuras

| Fase | Módulo | Estado |
|---|---|---|
| 1 | Arquitectura base (migraciones, modelos, seeders, tests) | ✅ Completo |
| 1 | Módulo de órdenes (CRUD, pago dividido, dividir orden) | ✅ Completo |
| 2 | Módulo de productos (CRUD, insumos, precio sugerido) | ✅ Completo |
| 2 | Módulo de caja (apertura/cierre, movimientos, observer de órdenes) | ✅ Completo |
| 3 | Dashboard con KPIs (ventas hoy/semana, top/bottom productos, proyección mensual) | ✅ Completo |
| 3 | Módulo mesero (POS móvil, mesa + notas por ítem, items agrupados por impresora) | ✅ Completo |
| 3 | Cargo por servicio en órdenes (% configurable, recalculo automático) | ✅ Completo |
| 3 | Agregar items a orden existente desde admin (solo pending) | ✅ Completo |
| 3 | Módulo de billeteras digitales (Nequi, Daviplata, Nubank — saldo, transacciones) | ✅ Completo |
| 3 | Gestión de categorías (CRUD UI) | ✅ Completo |
| 3 | Alertas de stock bajo en Dashboard + color coding en Products/Index | ✅ Completo |
| 3 | Audit trail en reversal de billeteras (expense COR-ORD-{id}) | ✅ Completo |
| 3 | Recibo imprimible de orden (`window.print()`, ticket-style) | ✅ Completo |
| 3 | Descuento/reposición automática de stock al pagar/cancelar orden (Observer) | ✅ Completo |
| 3 | Impuesto fijo configurable on/off en órdenes (`tax`/`tax_amount`, 3.5%) | ✅ Completo |
| 3 | Confirmación de método de pago (sub-paso en `orders/Show.vue`) | ✅ Completo |
| 4 | Mesas físicas como modelo (Table — selector en Waiter y admin Orders) | ✅ Completo |
| 4 | Gestión de proveedores (CRUD UI) | ✅ Completo |
| 4 | Módulo empleados (CRUD, asociar orden a empleado, filtro en Orders) | ✅ Completo |
| 5 | Login con usuario/clave para roles Mesero/Cocinero + zonas restringidas (`/waiter`, `/kitchen`) | ✅ Completo |
| 5 | Módulo Cocina básico (`/kitchen` — cola por estación, marcar items/orden listos) | ✅ Completo |
| 5 | Registro público deshabilitado (`Features::registration()`, solo alta vía `/employees`/seeders) | ✅ Completo |
| 5 | Seeder de usuarios demo Mesero/Cocinero (`EmployeesSeeder`) | ✅ Completo |
| 5 | Módulo de impresión real (envío a impresoras configuradas) | Pendiente |
| 6 | Reservas | Pendiente |
| 7 | Nómina de empleados | Pendiente |
