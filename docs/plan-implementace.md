# Plán implementace

Pracovní plán pro dokončení realizační části (viz „Co zbývá dokončit“ v `dokumentace-tym-07.docx` a checklist v [README.md](README.md)). Je psaný pro postupné zpracování krok za krokem, klidně jiným modelem nebo dalším členem týmu.

Pokrývá: testy, MariaDB, registraci a přihlášení, role, přidání a úpravu inzerátu s fotkou a místem na mapě, kontakt autora e-mailem, CSRF, můj účet, administraci a přípravu nasazení. Wireframy, testy použitelnosti, validátory a Word dokument se dělají ručně a tady nejsou.

## Pravidla pro každý krok

1. **Test first.** Nejdřív napiš test, spusť ho a ověř, že padá ze správného důvodu. Pak napiš minimální kód, aby prošel. Pak ukliď. Teprve pak commit.
2. Kód, názvy, komentáře a commity anglicky. Texty v UI česky.
3. Komentáře jen tam, kde kód sám nestačí, jednou větou. Žádné docblocky, které opakují signaturu.
4. Drž strukturu projektu (`src/` s namespace `App\`, `templates/`, `assets/`, `database/`). Nové složky jen ty, které plán jmenuje.
5. PHP 8.1 kompatibilní: žádné `readonly class`, typované konstanty, `json_validate()`, `#[\Override]`.
6. Každý výstup přes `e()`, každý odkaz přes `url()`, redirect přes `redirect()`.
7. Stránky musí fungovat bez JS. JS jen vylepšuje.
8. SQL vždy přes prepared statements s pozičními `?`. S `ATTR_EMULATE_PREPARES = false` nejde stejný pojmenovaný parametr použít dvakrát. Čísla do `LIMIT`/`OFFSET` přetypuj na `int` a vlož přímo do SQL.
9. Po každém kroku: `composer test` zelené, ruční kontrola v prohlížeči podle bodu „Ověření“, commit se zprávou uvedenou u kroku.
10. Nové stránky mimo veřejný obsah (přihlášení, účet, administrace, formuláře) mají ve view datech `'noindex' => true`.

## Předpoklady

- PHP 8.1+ s rozšířeními `pdo_mysql`, `gd` (s WebP), `mbstring`, `fileinfo`
- Composer, MariaDB 10.6+
- macOS: `brew install php composer mariadb`, pak `brew services start mariadb`

Lokálně vytvoř dvě databáze: `ztraty_nalezy` (vývoj) a `ztraty_nalezy_test` (testy, při každém běhu se maže).

```sql
CREATE DATABASE ztraty_nalezy CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;
CREATE DATABASE ztraty_nalezy_test CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;
```

## Cílová struktura (nové soubory)

```
composer.json, phpunit.xml, .env.testing.example
database/seed.php
src/Core/        App.php, Kernel.php, Response.php, Redirect.php, HttpError.php,
                 Database.php, Session.php, Csrf.php
src/Auth/        Auth.php, LoginResult.php, ItemPolicy.php
src/Forms/       Form.php, RegisterForm.php, LoginForm.php, ItemForm.php,
                 CategoryForm.php, BuildingForm.php (+ upravený ContactForm.php)
src/Mail/        MailMessage.php, Mailer.php, LogMailer.php, NativeMailer.php, ContactMail.php
src/Repositories/ PdoItemRepository.php, UserRepository.php, CategoryRepository.php,
                 BuildingRepository.php, MessageRepository.php
src/Uploads/     PhotoProcessor.php
src/Controllers/ AuthController.php, AccountController.php
src/Controllers/Admin/ ItemController.php, UserController.php,
                 CategoryController.php, BuildingController.php
templates/auth/  login.php, register.php
templates/account/ index.php
templates/items/ edit.php
templates/admin/ items.php, users.php, categories.php, buildings.php
templates/partials/ flash.php, item-form.php, admin-nav.php, login-prompt.php
templates/errors/ 403.php, 419.php
assets/js/modules/ location-picker.js, confirm.js
storage/logs/.gitkeep
tests/bootstrap.php
tests/Support/   DatabaseTestCase.php, FeatureTestCase.php, TestResponse.php,
                 Fixtures.php, FakeMailer.php
tests/Unit/, tests/Integration/, tests/Feature/
```

Smaže se `src/Repositories/JsonItemRepository.php`. JSON soubory v `database/mock/` zůstanou jako zdroj pro seed.

---

## Fáze 0: testovací infrastruktura

### 0.1 PHPUnit

- `composer.json`:
  - `require-dev`: `phpunit/phpunit: ^10.5` (verze 11 vyžaduje PHP 8.2)
  - `config.platform.php: "8.1.0"`
  - `autoload-dev`: `psr-4` `"Tests\\": "tests/"`
  - `scripts.test`: `phpunit`
  - Aplikace dál používá vlastní autoloader z `src/bootstrap.php`, Composer je jen pro testy (na server se `vendor/` nenahrává).
- `phpunit.xml`: bootstrap `tests/bootstrap.php`, testsuites `unit` (`tests/Unit`), `integration` (`tests/Integration`), `feature` (`tests/Feature`), `cacheDirectory=".phpunit.cache"`, `failOnWarning`, `failOnRisky`.
- `tests/bootstrap.php`: `require vendor/autoload.php`, `require src/bootstrap.php`, pak `App\Core\Env::load(ROOT_PATH . '/.env.testing')` (přepíše hodnoty z `.env`) a nastav `$_ENV['APP_URL'] = 'http://localhost'`, `$_ENV['BASE_PATH'] = ''`, `$_ENV['MAIL_DRIVER'] = 'log'`.
- `.env.testing.example` s `DB_NAME=ztraty_nalezy_test` a prázdnými `DB_USER`, `DB_PASS`.
- `.gitignore`: přidej `.env.testing`, `.phpunit.cache/`, `storage/logs/*`, `!storage/logs/.gitkeep`.
- `README.md`: sekce „Testy“ (`composer install`, `cp .env.testing.example .env.testing`, `composer test`).

### 0.2 Charakterizační testy existujícího kódu

Testy, které popíšou současné chování, než do něj začneme sahat. Mají projít hned.

`tests/Unit/HelpersTest.php`
- `fold('Klíče ŽLUŤOUČKÝ')` → `'klice zlutoucky'`
- `slugify('Ztracené klíče, černá klíčenka!')` → `'ztracene-klice-cerna-klicenka'`
- `plural_form` pro 0, 1, 2, 4, 5 → `many, one, few, few, many`
- `url('/inzeraty', ['type' => 'lost', 'q' => ''])` → `/inzeraty?type=lost` (prázdné hodnoty se vynechají)
- `url()` s `$_ENV['BASE_PATH'] = '/~login/ztraty'` → prefix; v `tearDown` vrať `BASE_PATH` na `''`
- `item_photo_path()` vrací `null` pro kategorii `doklady`, jinak `/images/items/2.webp` a `-thumb.webp`
- `item_code(['id' => 7])` → `#007`

`tests/Unit/Repositories/ItemQueryTest.php`
- neplatný `type` → `null`, prázdný řetězec → `null`, `page` menší než 1 → 1, pole místo řetězce → `null`
- `offset()` pro stranu 3 → 18

`tests/Unit/Forms/ContactFormTest.php`: zatím současná pole (`name`, `email`, `message`), ve fázi 4 se přepíše.

Commit: `Add PHPUnit with tests for helpers and filters`

### 0.3 Kernel místo `exit`

`redirect()` a `abort()` teď volají `exit`, takže controllery nejdou testovat. Nahraď to výjimkami, které zachytí jedno místo.

Nejdřív testy:

`tests/Unit/Core/RouterTest.php`
- `get('/inzerat/{id:\d+}/{slug}', $closure)` předá closure `id` a `slug` jako pojmenované argumenty
- neodpovídající cesta → vyhodí `HttpError` se `status === 404`
- POST handler se nevolá pro GET

`tests/Unit/Core/KernelTest.php` (volá `Kernel::handle()` se skutečnými routami)
- `GET /neexistuje` → `Response` se statusem 404 a tělem obsahujícím „Stránka nenalezena“
- `GET /robots.txt` → status 200, tělo obsahuje `Sitemap:`

Implementace:

```php
// src/Core/Redirect.php
final class Redirect extends \RuntimeException
{
    public function __construct(public readonly string $location, public readonly int $status = 302)
    {
        parent::__construct("Redirect to {$location}");
    }
}
```

- `src/Core/HttpError.php`: stejný vzor, vlastnost `status`.
- `src/Core/Response.php`: `__construct(public readonly int $status, public readonly string $body, public readonly ?string $location = null)`, metoda `send()` (nastaví `http_response_code`, případně `Location`, vypíše tělo).
- `src/Core/Kernel.php`, statická `handle(string $method, string $path): Response`:
  - vytvoří `Router`, `require ROOT_PATH . '/src/routes.php'` (proměnná `$router` musí být v metodě)
  - `ob_start()`, `dispatch()`, vrátí `new Response(http_response_code() ?: 200, ob_get_clean())`
  - `catch (Redirect)`: `ob_end_clean()`, vrátí response s `location`
  - `catch (HttpError)`: `ob_end_clean()`, vrátí response s tělem `render_error($status)`
- `helpers.php`:
  - `redirect()` vyhodí `new Redirect(url($path), $status)`
  - `abort()` vyhodí `new HttpError($status)`
  - nová `render_error(int $status): string`: pro `/api/*` nastaví JSON hlavičku a vrátí `json_encode(['error' => $status])`, jinak `render('layout', …)` se šablonou `errors/{status}` a titulkem z mapy `[403 => 'Přístup odepřen', 404 => 'Stránka nenalezena', 419 => 'Platnost formuláře vypršela']`, vždy `noindex`
- `public/index.php`: `App\Core\Kernel::handle($_SERVER['REQUEST_METHOD'], current_path())->send();`
- Šablony `errors/403.php` a `errors/419.php` podle vzoru `404.php`. Text pro 419: „Formulář byl otevřený moc dlouho. Vraťte se, obnovte stránku a odešlete ho znovu.“

V CLI vrací `http_response_code()` `false`, takže testy kontrolují status jen u redirectů a chyb. U validačních chyb (422) testy kontrolují obsah stránky.

Ověření: web běží jako dřív, `/neexistuje` vrátí 404 stránku, špatný slug detailu přesměruje 301.

Commit: `Handle redirects and errors in a testable kernel`

---

## Fáze 1: MariaDB

### 1.1 Schéma a připojení

`database/schema.sql`:
- na začátek `DROP TABLE IF EXISTS messages, photos, items, buildings, categories, users;`
- `buildings`: přidej `short VARCHAR(20) NOT NULL` (šablony používají `$item['building']['short']`)
- `items`: přidej `search_text TEXT NOT NULL`, bez diakritiky a malými písmeny (`fold()` z názvu, popisu a místa). Repozitář ho plní při každém zápisu. Důvod: `utf8mb4_czech_ci` bere č a c jako různé znaky, takže `LIKE` by „klice“ v „Klíče“ nenašel.
- `photos`: `UNIQUE KEY uq_photos_item (item_id)`, jedna fotka na inzerát
- `users.email` ukládat vždy malými písmeny

`src/Core/Database.php`:
- `static connect(): PDO` z `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, DSN s `charset=utf8mb4`, atributy `ERRMODE_EXCEPTION`, `DEFAULT_FETCH_MODE = FETCH_ASSOC`, `EMULATE_PREPARES = false`
- `static runSqlFile(PDO $db, string $file): void`: rozdělí soubor podle `/;\s*$/m`, prázdné části přeskočí, každou spustí přes `exec()`

`.env.example`: smaž `DATA_SOURCE`, přidej `MAIL_DRIVER=log`, `MAIL_FROM=noreply@example.com`.

`tests/Support/DatabaseTestCase.php`:
- `protected static ?PDO $db`
- `setUpBeforeClass()`: připojení; když selže, `markTestSkipped('Test database not available')`. Jednou za běh (statický příznak) spustí `schema.sql`.
- `setUp()`: `beginTransaction()`, `tearDown()`: `rollBack()`

`tests/Support/Fixtures.php` (trait pro DB testy), každá metoda vloží řádek a vrátí ho jako pole:
- `createUser(array $overrides = [])`: výchozí jméno, unikátní e-mail (`'user' . uniqid() . '@example.com'`), heslo `password123`, role `user`
- `createCategory(array $overrides = [])`, `createBuilding(array $overrides = [])`
- `createItem(array $overrides = [])` přidej až v kroku 1.3: když chybí `user_id`, `category_id` nebo `building_id`, vytvoří je. Vkládá přes `PdoItemRepository::create()`, aby se plnil `search_text`. Podporuje i `status` a `photo`.

Test `tests/Integration/DatabaseTest.php`: po spuštění schématu existuje všech 6 tabulek.

Commit: `Add MariaDB connection and test database setup`

### 1.2 Kategorie a budovy

`CategoryRepository(PDO $db)`, testy v `tests/Integration/Repositories/CategoryRepositoryTest.php`:
- `all()` seřazené podle `name`, každý řádek má `id`, `slug`, `name`
- `find(int $id)`, `findBySlug(string $slug)` vrací `null`, když neexistuje
- `create(string $name, string $slug): int`, `update(int $id, string $name, string $slug): void`
- `isUsed(int $id): bool` je `true`, když na kategorii odkazuje inzerát
- `delete(int $id): void`

`BuildingRepository` stejně, navíc sloupce `short`, `lat`, `lng`. `lat` a `lng` vracet jako `float`. `create`/`update` berou pole `['name', 'short', 'slug', 'lat', 'lng']`.

Commit: `Add category and building repositories`

### 1.3 PdoItemRepository

Upravené rozhraní `ItemRepository`:
- odebrat `categories()` a `buildings()`
- přidat `forUser(int $userId): array`, `forAdmin(?string $status = null): array`, `create(array $data): int`, `update(int $id, array $data): void`, `setStatus(int $id, string $status): void`, `setPhoto(int $id, ?string $path): void`, `delete(int $id): void`

Tvar inzerátu musí zůstat stejný jako z `JsonItemRepository` (šablony se nemění): `id` (int), `user_id` (int), `type`, `status`, `title`, `slug`, `description`, `location`, `lat`/`lng` (float), `date` (`Y-m-d`), `photo` (cesta bez přípony nebo `null`), `category` (`id`, `slug`, `name`), `building` (`id`, `slug`, `name`, `short`, `lat`, `lng`). Jeden `SELECT` s `JOIN categories`, `JOIN buildings`, `LEFT JOIN photos` a aliasy `category_slug` atd., soukromá metoda `hydrate(array $row)`.

Veřejné metody (`search`, `all`, `latest`, `related`, `stats`) nikdy nevrací `status = 'hidden'`. `find()` vrací inzerát v jakémkoli stavu, o zobrazení rozhoduje controller.

Testy `tests/Integration/Repositories/PdoItemRepositoryTest.php`. Chování se řídí podle `JsonItemRepository` (referenční implementace):
- `search()` bez filtrů vrátí aktivní i vrácené, ne skryté, od nejnovějšího `date` (při shodě vyšší `id` první)
- filtry `type`, `category` (slug), `building` (slug) a jejich kombinace
- `q = 'klice'` najde „Klíče“ v názvu, popisu i místě; `q = '50%'` nenajde všechno (escapuj `%`, `_` a `\` v `LIKE`)
- stránkování: 10 inzerátů, `page = 2` vrátí 1 položku, `total = 10`, `markers` mají všech 10 s klíči `lat`, `lng`, `title`, `type`, `url`
- `find()` vrátí i skrytý inzerát, neexistující id → `null`, hodnoty mají správné typy (`assertSame`)
- `latest(2)` vrátí 2 nejnovější
- `related()`: stejná budova má přednost před stejnou kategorií, vynechá sám sebe, vrácené, skryté a inzeráty bez shody
- `stats()` počítá `found` a `lost` jen z aktivních, `returned` zvlášť, skryté vůbec
- `create()` vygeneruje `slug` přes `slugify(title)` (oříznout na 160 znaků) a `search_text`, vrátí id
- `update()` přegeneruje `slug` i `search_text`
- `setPhoto($id, 'uploads/items/5-ab12')` pak `find()` vrátí `photo`; `setPhoto($id, null)` fotku odebere (`DELETE` + `INSERT`)
- `setStatus()`, `delete()`
- `forUser()` vrátí všechny stavy daného uživatele, žádné cizí
- `forAdmin('hidden')` jen skryté, `forAdmin()` všechny; každý řádek navíc `author_email`

Commit: `Add PDO item repository`

### 1.4 Přepnutí na databázi

`src/Core/App.php`, jednoduchý registr služeb (aby se v testech dala služba podstrčit):

```php
final class App
{
    private static array $services = [];

    public static function set(string $id, object $service): void { self::$services[$id] = $service; }
    public static function reset(): void { self::$services = []; }

    public static function db(): \PDO { return self::$services['db'] ??= Database::connect(); }
    public static function items(): ItemRepository { return self::$services['items'] ??= new PdoItemRepository(self::db()); }
    // categories(), buildings() stejně; další služby přibývají v dalších fázích
}
```

- `Controller::items()` smaž, controllery volají `App::items()`, `App::categories()`, `App::buildings()`
- `ListingMeta::for(ItemQuery $query, array $categories, array $buildings)`; dopiš `tests/Unit/Seo/ListingMetaTest.php`: jeden filtr → vlastní nadpis a canonical, kombinace a strana 2 → canonical `/inzeraty`, `q` → `noindex`, neznámý slug → výchozí nadpis
- uprav volání v `ItemController`, `Api\ItemController`, `SeoController`
- smaž `JsonItemRepository.php` a TODO v `Controller.php`

`database/seed.php` (CLI, `php database/seed.php`):
- načte `src/bootstrap.php`, spustí `schema.sql` (vývojová DB se tím smaže, vypiš to)
- vloží kategorie a budovy z `database/mock/*.json` přes repozitáře
- vytvoří uživatele „Tým Ztráty a nálezy“ (`tym@ztraty.example`) s `password_hash` nastaveným na `'!'`, přes který se nikdo nepřihlásí
- vloží inzeráty přes `PdoItemRepository::create()` (zachovej id z JSON kvůli URL: `create()` přijme volitelné `id`), pak `setStatus` a `setPhoto`
- na konci vypíše, jak udělat admina: `UPDATE users SET role = 'admin' WHERE email = '…';`

README: postup `php database/seed.php`.

Ověření: úvod, výpis s filtry a AJAXem, detail, `/sitemap.xml` vypadají stejně jako před změnou (porovnej s `docs/img/`).

Commit: `Switch from mock JSON to MariaDB`

---

## Fáze 2: session, CSRF a přihlášení

### 2.1 Session a CSRF

`src/Core/Session.php`, obal nad polem, v testech nad obyčejným polem:

```php
private array $data;

public function __construct(array &$data)
{
    $this->data = &$data;
}
```

- `get(string $key, mixed $default = null)`, `set`, `remove`, `has`
- `flash(string $type, string $message)`, `pullFlashes(): array` (vrátí a smaže, typy `success`/`error`)
- `regenerate()`: `session_regenerate_id(true)` jen pokud `session_status() === PHP_SESSION_ACTIVE`
- `static start()`: `session_name('ztraty_session')`, `session_set_cookie_params` (`path` = `base_path() ?: '/'`, `httponly`, `samesite=Lax`, `secure` při HTTPS), `session_start()`

`src/Core/Csrf.php(Session)`: `token()` vytvoří jednou za session `bin2hex(random_bytes(32))`, `check(?string $token): bool` přes `hash_equals`.

Testy `tests/Unit/Core/SessionTest.php` a `CsrfTest.php`:
- zápis přes `Session` se projeví v původním poli
- flash zprávy se po `pullFlashes()` smažou
- `token()` vrací pořád stejnou hodnotu, `check()` odmítne `null`, prázdný a cizí token

Napojení:
- `App::session()` (`$_SESSION ??= []`), `App::csrf()`
- `public/index.php`: `Session::start()` před `Kernel::handle()`
- `Kernel`: u `POST`, když `!App::csrf()->check($_POST['_token'] ?? null)`, vyhoď `HttpError(419)`
- helper `csrf_field(): string` vrátí `<input type="hidden" name="_token" value="…">`
- přidej `csrf_field()` do kontaktního formuláře v `items/show.php`
- `partials/flash.php` vypíše `App::session()->pullFlashes()` jako `alert alert--success|error` s `role="status"`, vlož ho v `layout.php` na začátek `<main>`

`tests/Support/FeatureTestCase.php` (dědí `DatabaseTestCase`, používá `Fixtures`):
- `setUp()`: `App::reset()`, `App::set('db', self::$db)`, `App::set('session', new Session($this->session))`, `App::set('mailer', $this->mailer = new FakeMailer())`
- `get(string $path, array $query = [])` a `post(string $path, array $data = [], bool $withToken = true)`: nastaví `$_GET`, `$_POST`, `$_FILES = []`, `$_SERVER['REQUEST_METHOD']`, `$_SERVER['REQUEST_URI']`, zavolá `Kernel::handle()` a vrátí `TestResponse`. `post()` doplní `_token` z `App::csrf()->token()`.
- `actingAs(array $user)`: `$this->session['user_id'] = $user['id']`
- `TestResponse`: `assertOk()`, `assertStatus(int)`, `assertRedirect(string $path)` (porovná s `url($path)`), `assertSee(string)`, `assertDontSee(string)`

`tests/Feature/CsrfTest.php`: POST kontaktního formuláře bez tokenu → 419, s tokenem → neskončí 419.

Commit: `Add sessions, flash messages and CSRF protection`

### 2.2 Uživatelé

`UserRepository(PDO)`, testy v `tests/Integration/Repositories/UserRepositoryTest.php`:
- `create(string $name, string $email, string $password): int` uloží `password_hash($password, PASSWORD_DEFAULT)` a e-mail malými písmeny
- `find(int $id)`, `findByEmail(string $email)` bez ohledu na velikost písmen; výsledek obsahuje `id`, `name`, `email`, `password_hash`, `role`, `is_blocked` (bool)
- `all(): array` s počtem inzerátů (`items_count`), od nejnovějšího
- `setBlocked(int $id, bool $blocked)`, `setRole(int $id, string $role)` (jen `user`/`admin`, jinak `InvalidArgumentException`)

Commit: `Add user repository`

### 2.3 Formuláře

`src/Forms/Form.php`, abstraktní základ, aby se neopakoval kód z `ContactForm`:
- `public array $errors = []`
- `protected static function string(array $input, string $key): string` (trim, ne-řetězec → `''`)
- `protected function check(array $rules): bool`: rules jsou `field => ?string chyba`, uloží neprázdné do `$errors` a vrátí, jestli je prázdné
- `protected static function inCampus(float $lat, float $lng): bool` v rozsahu lat 50.125–50.135, lng 14.365–14.380

`ContactForm` přepiš na `Form` (testy z 0.2 musí dál projít).

`RegisterForm` (`name`, `email`, `password`, `password_confirmation`), `validate(bool $emailTaken): bool`. Testy:
- prázdné jméno, jméno přes 100 znaků
- neplatný e-mail, obsazený e-mail („Účet s tímto e-mailem už existuje.“)
- heslo kratší než 8 znaků, neshodné potvrzení
- e-mail se normalizuje na malá písmena, platný vstup projde

`LoginForm` (`email`, `password`): obě pole povinná.

Commit: `Add registration and login forms`

### 2.4 Auth

`src/Auth/LoginResult.php`: `enum LoginResult { case Success; case Invalid; case Blocked; }`

`src/Auth/Auth.php(Session $session, UserRepository $users)`:
- `attempt(string $email, string $password): LoginResult`. Při úspěchu `login()`. Špatný e-mail i heslo vrací stejně `Invalid`.
- `login(array $user)`: `regenerate()`, uloží `user_id`
- `logout()`: odebere `user_id`, `regenerate()`
- `user(): ?array`: načte podle `user_id` (v rámci requestu jen jednou). Smazaný nebo zablokovaný uživatel → `logout()` a `null`.
- `check(): bool`, `isAdmin(): bool`

Helper `safe_redirect_path(?string $path): string`: povolí jen interní cestu (začíná `/`, ne `//`, ne `/\`), jinak `/`.

Testy `tests/Integration/Auth/AuthTest.php` a `HelpersTest`:
- správné heslo → `Success` a `user()` vrátí uživatele
- špatné heslo, neexistující e-mail → `Invalid`, nikdo není přihlášený
- zablokovaný → `Blocked`; zablokovaný po přihlášení → `user()` vrátí `null`
- `logout()` → `check()` je `false`
- `safe_redirect_path('//evil.com')` → `/`, `'https://x'` → `/`, `'/nahlasit?type=lost'` zůstane

`App::users()`, `App::auth()`, helper `auth(): App\Auth\Auth`.

V base `Controller`:
- `requireUser(): array`: když nikdo není přihlášený, `redirect('/prihlaseni?zpet=' . rawurlencode(<aktuální cesta s query>))`
- `requireAdmin(): array`: `requireUser()`, pak bez role admin `abort(403)`

Commit: `Add authentication service`

### 2.5 Stránky registrace a přihlášení

Routy:
- `GET|POST /registrace` → `AuthController::registerForm|register`
- `GET|POST /prihlaseni` → `loginForm|login`
- `POST /odhlaseni` → `logout`

Chování:
- registrace úspěšná → přihlásí, flash „Účet je vytvořený.“, redirect na `zpet` nebo `/`
- přihlášení úspěšné → redirect na `safe_redirect_path($_GET['zpet'] ?? null)`; `Invalid` → „Nesprávný e-mail nebo heslo.“; `Blocked` → „Účet je zablokovaný. Ozvěte se správci.“
- přihlášený uživatel na `/prihlaseni` nebo `/registrace` → redirect `/ucet`
- odhlášení → flash „Byli jste odhlášeni.“, redirect `/`
- `zpet` se předává ve skrytém poli formuláře

Šablony `auth/login.php` a `auth/register.php` přes `partials/form-field` (typ `password` a `autocomplete` `current-password`/`new-password`), odkaz mezi nimi navzájem. Uprav `form-field.php`: typ `password` hodnotu nevypisuje.

Hlavička (`partials/header.php`):
- nepřihlášený: odkaz „Přihlásit se“
- přihlášený: „Můj účet“, pro admina „Administrace“, tlačítko „Odhlásit“ jako malý POST formulář s `csrf_field()`
- „Nahlásit věc“ zůstává. Styly v `assets/css/layout.css`, ověř šířku 375 px (hlavička se smí zalomit, ale nesmí přetékat).

Feature testy `tests/Feature/AuthTest.php`:
- `GET /prihlaseni` → 200, obsahuje formulář
- registrace s platnými daty → redirect, uživatel v DB, v session `user_id`
- registrace s obsazeným e-mailem → zobrazí chybu, nikdo není přihlášený
- přihlášení správně → redirect na `zpet`; `zpet=//evil.com` → redirect `/`
- přihlášení špatně a zablokovaný účet → příslušná hláška
- odhlášení → `user_id` zmizí
- přihlášený uživatel vidí v hlavičce „Můj účet“, admin i „Administrace“, běžný uživatel ne

`robots.txt`: přidej `Disallow: /admin/`, `Disallow: /ucet`.

Commit: `Add registration, login and logout pages`

---

## Fáze 3: přidání a úprava inzerátu

### 3.1 Oprávnění

`src/Auth/ItemPolicy.php`, statické čisté funkce:
- `canManage(?array $user, array $item): bool`: autor nebo admin
- `canView(?array $user, array $item): bool`: neskrytý inzerát vidí každý, skrytý jen `canManage`
- `canContact(?array $user, array $item): bool`: přihlášený, není autor, inzerát je `active`

Testy `tests/Unit/Auth/ItemPolicyTest.php`: host, autor, cizí uživatel a admin proti aktivnímu, vrácenému a skrytému inzerátu.

Commit: `Add item policy`

### 3.2 ItemForm

`src/Forms/ItemForm.php` (dědí `Form`):
- pole: `type`, `title`, `category` (slug), `building` (slug), `location`, `date`, `description`, `lat`, `lng` (řetězce), `photo` (pole z `$_FILES` nebo `null`)
- `fromArray(array $input, ?array $photo = null)`, `fromItem(array $item)` pro předvyplnění úpravy
- `validate(array $categories, array $buildings): bool`, při úspěchu si zapamatuje nalezenou kategorii a budovu
- `toData(): array` → `type`, `category_id`, `building_id`, `title`, `description`, `location`, `date`, `lat`, `lng`. Když `lat`/`lng` chybí, vezmou se souřadnice budovy.
- `hasPhoto(): bool`: soubor nahraný a kategorie není `doklady`

Pravidla a testy `tests/Unit/Forms/ItemFormTest.php` (každé pravidlo zvlášť plus jeden úplně platný vstup):
- `type` musí být klíč `ItemQuery::TYPES`
- `title` 3–150 znaků
- `category` a `building` musí existovat mezi předanými řádky
- `location` povinné, max 150
- `date` přesně `Y-m-d` (`DateTime::createFromFormat('!Y-m-d', …)` a zpětné porovnání s `format()`), ne v budoucnosti
- `description` 20–5000 znaků
- `lat`/`lng` obě prázdné, nebo obě číselné a `inCampus()`; jen jedna vyplněná → chyba
- `photo`: `UPLOAD_ERR_NO_FILE` je v pořádku; jiná chyba, velikost nad 5 MB nebo MIME (`finfo` nad `tmp_name`) mimo `image/jpeg`, `image/png`, `image/webp` → chyba. V testu vytvoř skutečný malý PNG přes GD do `sys_get_temp_dir()` a textový soubor jako špatný typ.
- `toData()` bez souřadnic vrátí souřadnice budovy
- `hasPhoto()` je `false` pro kategorii `doklady`

Commit: `Add item form validation`

### 3.3 Fotky

`src/Uploads/PhotoProcessor.php(string $publicDir = PUBLIC_PATH)`:
- `store(string $sourceFile, string $basePath): void`: `imagecreatefromstring()`, otočení podle EXIF (jen pokud existuje `exif_read_data`), ořez na střed do čtverce, uloží `{basePath}.webp` 1000×1000 a `{basePath}-thumb.webp` 240×240 (`imagewebp`, kvalita 80). Stejné rozměry mají testovací fotky.
- `delete(?string $basePath): void`: smaže obě velikosti, jen pokud cesta začíná `uploads/` (fotky ze seedu v `images/items/` nemazat)
- `static newPath(int $itemId): string` → `uploads/items/{id}-{8 hex znaků}`. Nová cesta při každém nahrání, aby prohlížeč nezobrazoval starou fotku z cache.

Testy `tests/Unit/Uploads/PhotoProcessorTest.php` (`markTestSkipped`, když chybí `imagewebp`), pracuj v dočasném adresáři:
- PNG 1200×800 → vzniknou dva soubory, `getimagesize` 1000×1000 a 240×240, typ WebP
- `delete()` je smaže; `delete('images/items/2')` nic nesmaže
- `newPath(5)` odpovídá `#^uploads/items/5-[0-9a-f]{8}$#`

`App::photos()`. Zkontroluj, že `public/uploads/items/` vznikne (`mkdir` s `recursive`), pokud chybí.

Commit: `Add photo processing`

### 3.4 Pole formuláře

Rozšiř `partials/form-field.php`, ať se neopakuje logika labelu, nápovědy a chyb:
- `required` jako volba (výchozí `true`)
- typy `date` (`max` = dnešek), `password`, `file` (`accept`, bez `value`), `select` (`options` jako `[['slug' => …, 'name' => …]]`, první volba „Vyberte…“ s prázdnou hodnotou)
- volitelné `attributes` (pole) pro `min`, `max`, `accept` a podobně

Testy `tests/Unit/Templates/FormFieldTest.php` (`render('partials/form-field', [...])`):
- s chybou má `aria-invalid="true"` a `aria-describedby` odkazuje na id chyby
- `select` označí vybranou volbu `selected`
- `password` a `file` nevypíšou hodnotu
- hodnota s `<script>` je escapovaná

Commit: `Support more field types in form partial`

### 3.5 Controller a stránky

Routy:
- `POST /nahlasit` → `ItemController::store`
- `GET|POST /ucet/inzeraty/{id:\d+}/upravit` → `edit|update`
- `POST /ucet/inzeraty/{id:\d+}/vraceno` → `markReturned`
- `POST /ucet/inzeraty/{id:\d+}/smazat` → `destroy`

Cesty pod `/ucet/…` nekolidují s `/inzerat/{id}/{slug}`.

`ItemController`:
- `create()`: když je zvolený typ a uživatel přihlášený, předá `ItemForm` a seznamy kategorií a budov; host uvidí `partials/login-prompt.php` (text a odkaz na přihlášení i registraci se `zpet`)
- `store()`: `requireUser()`, validace; při chybě znovu vykreslí `create` s chybami (status 422); při úspěchu `create()` v repozitáři, případně fotka (`is_uploaded_file` → `PhotoProcessor::store` → `setPhoto`), flash „Inzerát je zveřejněný.“, redirect 303 na detail
- `edit()`/`update()`: `findManageable(int $id)` (soukromá: najde, jinak 404, bez `canManage` 403); `update` při nové fotce smaže starou
- `markReturned()`: `setStatus('returned')`, flash, redirect na detail
- `destroy()`: smaže fotku i inzerát, flash, redirect `/ucet`
- `show()`: když `!canView` → 404; skrytý inzerát vidí autor a admin s upozorněním „Inzerát je skrytý administrátorem.“; pro `canManage` panel s odkazem Upravit a tlačítky Označit jako vrácené a Smazat (POST formuláře s `csrf_field()` a `data-confirm`)

Šablony:
- `partials/item-form.php`: společný formulář pro `create` i `edit`, `enctype="multipart/form-data"`, `csrf_field()`, typ jako skryté pole, pole podle 3.2, mapa `<div data-location-picker …>` se skrytými `lat` a `lng`, nápověda u fotky „Fotky dokladů nezveřejňujeme.“
- `items/create.php`: nahraď TODO
- `items/edit.php`: nadpis „Upravit inzerát“, drobečková navigace, náhled současné fotky

Feature testy `tests/Feature/ItemManagementTest.php`:
- host: `GET /nahlasit?type=found` ukáže výzvu k přihlášení; `POST /nahlasit` → redirect na `/prihlaseni?zpet=…`
- přihlášený: platný `POST /nahlasit` → inzerát v DB s `user_id`, redirect na detail, flash
- neplatný POST → stránka s chybami, nic se neuloží
- bez souřadnic → uloží se souřadnice budovy
- autor: `GET …/upravit` 200, `POST …/upravit` změní název i slug
- cizí uživatel: úprava, vráceno i smazání → 403; admin může
- `POST …/vraceno` → stav `returned`, detail ukazuje hlášku o vrácení
- `POST …/smazat` → inzerát pryč, redirect `/ucet`
- skrytý inzerát: host 404, autor 200 s upozorněním

Commit: `Add creating, editing and deleting items`

### 3.6 Výběr místa na mapě

`assets/js/modules/location-picker.js`, registrace v `main.js` pro `[data-location-picker]`:
- znovu použij nastavení mapy z `map.js`: vytáhni z něj `createMap(el)` (dlaždice, zoom, české popisky) a exportuj ho, ať se kód neopakuje
- klik do mapy přesune značku a zapíše `lat`/`lng` (6 desetinných míst) do skrytých polí
- změna budovy v selectu (`data-buildings` s JSON souřadnicemi) přesune mapu a značku na budovu, pokud uživatel ještě neklikl vlastní místo
- při úpravě začni na uložených souřadnicích
- bez JS mapa není vidět a uloží se souřadnice budovy (zajišťuje server)
- přístupnost: `role="region"`, `aria-label="Vyberte místo na mapě"`, pod mapou text „Klikněte do mapy, kde jste věc našli nebo ztratili. Když místo nevyberete, použije se budova.“

`assets/js/modules/confirm.js` pro `[data-confirm]`: při odeslání formuláře zavolá `confirm(text)`, při zrušení `preventDefault()`.

Ověření ručně: přidání se zvolením místa, bez JS (vypnout v DevTools), na mobilu 375 px, klávesnicí.

Commit: `Add location picker and confirm dialogs`

---

## Fáze 4: kontakt autora e-mailem

Podle tabulky rolí kontaktuje autora jen přihlášený uživatel. Jméno a e-mail se proto berou z účtu.

### 4.1 Mail

- `src/Mail/MailMessage.php`: `to`, `subject`, `body`, `replyTo` (readonly vlastnosti)
- `src/Mail/Mailer.php`: rozhraní `send(MailMessage $message): bool`
- `LogMailer(string $file)`: připíše zprávu do `storage/logs/mail.log` (výchozí lokálně)
- `NativeMailer(string $from)`: `mail()` s hlavičkami `From`, `Reply-To`, `Content-Type: text/plain; charset=UTF-8`, předmět přes `mb_encode_mimeheader`. Odmítni adresy a předmět s `\r` nebo `\n` (header injection).
- `App::mailer()` podle `MAIL_DRIVER` (`log`|`mail`)
- `ContactMail::create(array $item, array $author, array $sender, string $body): MailMessage`: komu autor, `replyTo` odesílatel, předmět „Zpráva k inzerátu #007: Název“, tělo s textem zprávy, jménem odesílatele a odkazem `absolute_url(item_path($item))`
- `tests/Support/FakeMailer.php`: ukládá zprávy do pole `$sent`

Testy:
- `tests/Unit/Mail/ContactMailTest.php`: adresát, `replyTo`, předmět, tělo obsahuje zprávu a absolutní odkaz
- `tests/Unit/Mail/LogMailerTest.php`: zapíše do dočasného souboru
- `tests/Unit/Mail/NativeMailerTest.php`: adresa s `\n` → `InvalidArgumentException` (`mail()` se nevolá)

Commit: `Add mailer and contact e-mail`

### 4.2 Zprávy

- `MessageRepository(PDO)`: `create(int $itemId, int $senderId, string $body): int`, `countForItem(int $itemId): int`; integrační test
- `ContactForm`: jen pole `message` (20–5000 znaků), uprav testy
- `ItemController::contact()`: `requireUser()`; když `!canContact` → redirect na detail; validace; uloží zprávu, pošle mail; když `send()` vrátí `false`, flash chyba „Zprávu se nepodařilo odeslat e-mailem, zkuste to později.“; jinak redirect s `?odeslano=1#kontakt` jako dnes
- `items/show.php`: host uvidí místo formuláře `partials/login-prompt.php`, autor vlastního inzerátu žádný formulář, ostatní jen textové pole Zpráva. Smaž TODO.

Feature testy `tests/Feature/ContactTest.php`:
- host: formulář se nezobrazí, POST → redirect na přihlášení
- přihlášený: platná zpráva → záznam v `messages`, `FakeMailer` má 1 zprávu na e-mail autora s `replyTo` odesílatele
- krátká zpráva → chyba, nic se neodešle
- autor vlastního inzerátu ani vrácený inzerát formulář nemají a POST nic neodešle

Commit: `Send contact messages to item authors`

---

## Fáze 5: můj účet

- route `GET /ucet` → `AccountController::index` (`requireUser()`)
- `account/index.php`: jméno a e-mail, tlačítko „Nahlásit věc“, seznam vlastních inzerátů (`forUser`) jako tabulka nebo kompaktní karty: číslo, název (odkaz), typ, stav (aktivní, vrácené, skryté), datum, akce Upravit, Označit jako vrácené (jen aktivní) a Smazat. Prázdný stav: „Zatím nemáte žádný inzerát.“
- `noindex`, drobečková navigace

Feature testy `tests/Feature/AccountTest.php`: host → redirect na přihlášení; uživatel vidí jen své inzeráty i skryté; prázdný stav.

Commit: `Add account page with own items`

---

## Fáze 6: administrace

Všechny akce volají `requireAdmin()`. Stránky jsou `noindex`, mají společné `partials/admin-nav.php` (Inzeráty, Uživatelé, Kategorie, Budovy) a úpravy jdou přes POST s `csrf_field()`, flash a redirect zpět. Mazání má `data-confirm`.

Routy:
- `GET /admin` → redirect `/admin/inzeraty`
- `GET /admin/inzeraty?stav=` → `Admin\ItemController::index` (filtr `active|returned|hidden`)
- `POST /admin/inzeraty/{id:\d+}/stav` (`status` = `active|hidden`) → `setStatus`
- `POST /admin/inzeraty/{id:\d+}/smazat` → `destroy` (smaže i fotku)
- `GET /admin/uzivatele` → `Admin\UserController::index`
- `POST /admin/uzivatele/{id:\d+}/blokace` (`blocked` = `0|1`)
- `POST /admin/uzivatele/{id:\d+}/role` (`role` = `user|admin`)
- `GET|POST /admin/kategorie` → `Admin\CategoryController::index|store`
- `POST /admin/kategorie/{id:\d+}` → `update`, `POST /admin/kategorie/{id:\d+}/smazat` → `destroy`
- `/admin/budovy` stejně jako kategorie (`Admin\BuildingController`)

Mazání inzerátu (fotka + záznam) se v `ItemController::destroy` i v administraci opakuje. Přesuň ho do `protected function deleteItem(array $item)` v base `Controller`, která zavolá `App::photos()->delete($item['photo'])` a `App::items()->delete($item['id'])`.

Formuláře:
- `CategoryForm` (`name` 2–100, `slug` volitelný → `slugify(name)`, musí odpovídat `^[a-z0-9-]+$`), `validate(array $takenSlugs): bool`
- `BuildingForm` (`name`, `short` max 20, `slug`, `lat`, `lng` v `inCampus()`), `validate(array $takenSlugs): bool`
- unit testy pro každé pravidlo; `takenSlugs` jsou slugy ostatních řádků (při úpravě bez vlastního)

Pravidla:
- admin nemůže zablokovat sám sebe ani si odebrat roli → flash chyba
- kategorii nebo budovu s inzeráty nejde smazat (`isUsed`) → „Kategorii nejde smazat, používá ji X inzerátů.“
- chyba validace → flash s první chybou, redirect zpět

Šablony `admin/items.php` (tabulka: číslo, název, autor, typ, stav, datum, akce), `admin/users.php` (jméno, e-mail, role, počet inzerátů, blokace, akce), `admin/categories.php` a `admin/buildings.php` (každý řádek je vlastní formulář na úpravu, pod tabulkou formulář pro přidání). Tabulky na mobilu zabal do `overflow-x: auto` s `tabindex="0"` a `aria-label`.

Feature testy `tests/Feature/AdminTest.php`:
- host → redirect na přihlášení, běžný uživatel → 403 na všech `GET` i `POST` adresách administrace (data provider přes seznam rout)
- admin: skrytí inzerátu (zmizí z `/inzeraty` i ze `sitemap.xml`), zobrazení zpět, smazání
- blokace uživatele → ten se nepřihlásí; pokus zablokovat sám sebe → chyba, stav beze změny
- změna role
- přidání, úprava a smazání kategorie; smazání použité kategorie selže
- totéž pro budovu; souřadnice mimo kampus → chyba

Commity po částech: `Add admin item moderation`, `Add admin user management`, `Add admin categories and buildings`

---

## Fáze 7: úklid, dokumentace a nasazení

### 7.1 Kontrola

- `grep -rn TODO src templates assets`: nesmí nic zůstat
- sitemap neobsahuje skryté inzeráty ani stránky účtu a administrace
- Lighthouse přístupnost nových stránek (přihlášení, nahlásit s formulářem, účet, administrace) na 100
- W3C validátor na formuláři pro přidání a v administraci
- `npm run build` a test na produkčních assetech

### 7.2 Dokumentace

- `CLAUDE.md`: řádek o datech změň na „Data in MariaDB via PDO repositories, `database/seed.php` loads mock data from `database/mock/`.“, přidej `composer test` do Commands a krátkou poznámku o `App`, `Kernel` a testech (`tests/Unit`, `tests/Integration`, `tests/Feature`)
- `README.md`: spuštění s DB, seed, testy, jak udělat admina, `MAIL_DRIVER`
- `docs/README.md`: odškrtni hotové položky
- Word dokument (ručně): v kapitole Serverová část doplnit přihlášení, role, CSRF, hashování hesel přes `password_hash`, prepared statements a nahrávání fotek s převodem do WebP; aktualizovat „Co zbývá dokončit“

Commit: `Update docs for database, auth and admin`

### 7.3 Nasazení na školní server (ručně, podle README)

1. Ověř na serveru `phpinfo()`: verze PHP, `pdo_mysql`, `gd` s WebP, `fileinfo`, jestli funguje `mail()`. Když GD neumí WebP, je potřeba to vyřešit, než se nasazuje.
2. `.env` na serveru: `APP_ENV=prod`, `APP_DEBUG=0`, `APP_URL`, `BASE_PATH`, DB údaje, `MAIL_DRIVER=mail`, `MAIL_FROM`.
3. `npm run build` lokálně s produkčním `BASE_PATH`.
4. Nahraj projekt bez `node_modules/`, `vendor/`, `tests/`, `.env.testing`. Document root na `public/`, `storage/logs/` a `public/uploads/` zapisovatelné.
5. Databáze: lokálně `php database/seed.php`, pak `mysqldump ztraty_nalezy > dump.sql` a import přes phpMyAdmin. Admina nastav SQL příkazem z výstupu seedu.
6. Smoke test: registrace, přihlášení, přidání s fotkou, kontakt (dorazí e-mail?), administrace, `sitemap.xml`, `robots.txt`.
7. PageSpeed Insights a validátory na serveru, screenshoty do `docs/testing/`.

---

## Pořadí a závislosti

```
0.1 → 0.2 → 0.3 → 1.1 → 1.2 → 1.3 → 1.4 → 2.1 → 2.2 → 2.3 → 2.4 → 2.5
                                                                  ↓
                     6 ← 5 ← 4.2 ← 4.1 ← 3.6 ← 3.5 ← 3.4 ← 3.3 ← 3.2 ← 3.1
                     ↓
                     7
```

Fáze 4.1, 3.1 a 3.3 nezávisí na ničem z fáze 3, takže se dají dělat paralelně ve vlastních větvích (`feature/…`, PR do `main`).
