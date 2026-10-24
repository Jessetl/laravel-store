# Laravel Store API

API RESTful construida con Laravel para gestionar productos y registrar sus precios en diferentes divisas. Permite crear, consultar, actualizar y eliminar productos, asignarles precios en otras monedas y obtener, para cada precio, la conversión calculada a partir de la tasa de cambio.

Todas las respuestas son JSON, el acceso está protegido con tokens Bearer (Laravel Sanctum) y la documentación interactiva se genera automáticamente a partir del código.

## Contenido

1. [Tecnologías](#tecnologías)
2. [Requisitos](#requisitos)
3. [Cómo levantar el proyecto](#cómo-levantar-el-proyecto)
4. [Primeros pasos con la API](#primeros-pasos-con-la-api)
5. [Documentación interactiva (Swagger)](#documentación-interactiva-swagger)
6. [Colección de Postman](#colección-de-postman)
7. [Endpoints](#endpoints)
8. [Formato de respuestas y errores](#formato-de-respuestas-y-errores)
9. [Conversión de divisas](#conversión-de-divisas)
10. [Modelo de datos](#modelo-de-datos)
11. [Arquitectura y organización del código](#arquitectura-y-organización-del-código)
12. [Pruebas automatizadas](#pruebas-automatizadas)
13. [Seguridad](#seguridad)
14. [Decisiones de diseño y supuestos](#decisiones-de-diseño-y-supuestos)

---

## Tecnologías

| Herramienta | Versión | Para qué se usa |
|---|---|---|
| PHP | 8.3 o superior | Lenguaje del proyecto |
| Laravel | 13 | Framework: rutas, validación, Eloquent, manejo de errores |
| Laravel Sanctum | 4 | Autenticación con tokens Bearer |
| MySQL | 8.4 (Docker) | Base de datos de desarrollo |
| SQLite (en memoria) | — | Base de datos de las pruebas automatizadas |
| Scramble (`dedoc/scramble`) | 0.13 | Genera la documentación OpenAPI/Swagger leyendo el código |
| PHPUnit | 12 | Pruebas automatizadas |
| Laravel Pint | 1 | Formato de código (estilo PSR-12/Laravel) |
| Adminer | latest (Docker) | Interfaz web para revisar la base de datos |

## Requisitos

- **PHP 8.3+** con las extensiones `bcmath`, `pdo_mysql` y `pdo_sqlite` (esta última solo para correr las pruebas).
- **Composer 2**.
- **Docker** y **Docker Compose**, para levantar MySQL y Adminer.

> La aplicación corre directamente en tu máquina con `php artisan serve`; Docker solo se usa para la base de datos.

## Cómo levantar el proyecto

### 1. Instalar dependencias

```bash
composer install
```

### 2. Crear el archivo de entorno

```bash
cp .env.example .env
php artisan key:generate
```

El `.env.example` ya trae la configuración lista para el MySQL de Docker:

| Variable | Valor | Descripción |
|---|---|---|
| `APP_ENV` | `local` | En `local` se habilita la documentación Swagger |
| `APP_URL` | `http://localhost:8000` | URL base de la API |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3307` | MySQL expuesto solo en tu máquina, en el puerto 3307 para no chocar con un MySQL local |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `db_store` / `db_store` / `secret` | Credenciales que también usa Docker Compose para crear la base |
| `SESSION_DRIVER` | `array` | La API no usa sesiones (es *stateless*) |

### 3. Levantar MySQL con Docker

```bash
docker compose up -d
```

Esto crea dos contenedores:

- **`laravel-store-mysql`**: MySQL 8.4 en `127.0.0.1:3307`, con los datos guardados en el volumen `mysql-data`.
- **`laravel-store-adminer`**: Adminer en <http://localhost:8080>. Para entrar usa servidor `mysql`, usuario `db_store`, contraseña `secret` y base `db_store`.

Espera a que MySQL esté listo (columna *STATUS* en `healthy`):

```bash
docker compose ps
```

### 4. Crear las tablas y cargar datos iniciales

```bash
php artisan migrate --seed
```

Los *seeders* crean:

- **Un usuario administrador** para obtener el token de acceso:
  - Correo: `root@example.com`
  - Contraseña: `Abc.123*`
- **Cinco divisas** tomadas de una lista fija (dólar estadounidense, euro, libra esterlina, yen japonés y dólar canadiense). Se eligen al azar, así que pueden repetirse; consulta los IDs reales en Adminer, en la tabla `currencies`.

### 5. Iniciar el servidor

```bash
php artisan serve
```

La API queda disponible en `http://localhost:8000/api/v1`. Para confirmar que la aplicación responde:

```bash
curl http://localhost:8000/up
```

## Primeros pasos con la API

Todas las rutas, excepto el inicio de sesión, requieren un token. Este es el recorrido completo desde la terminal.

**1. Iniciar sesión y guardar el token**

```bash
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"root@example.com","password":"Abc.123*"}'
```

```json
{
    "access_token": "1|Q8LKPRy1LpQdptWkjbykxosi7nz...",
    "token_type": "Bearer"
}
```

```bash
export TOKEN="1|Q8LKPRy1LpQdptWkjbykxosi7nz..."
```

**2. Crear un producto**

En los ejemplos, la divisa `1` es la divisa base del producto y la `2` es otra divisa distinta. Como el *seeder* elige las divisas al azar, revisa en Adminer qué IDs existen y ajústalos.

```bash
curl -s -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"Laptop Pro 14","description":"Laptop de 14 pulgadas","price":"1200.00","currency_id":1,"tax_cost":"192.00","manufacturing_cost":"650.00"}'
```

**3. Registrar el precio del producto en otra divisa**

```bash
curl -s -X POST http://localhost:8000/api/v1/products/1/prices \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"currency_id":2,"price":"999.00"}'
```

**4. Consultar los precios del producto (con la conversión calculada)**

```bash
curl -s http://localhost:8000/api/v1/products/1/prices \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

> Envía siempre el encabezado `Accept: application/json`. Aunque la API responde en JSON de todas formas, es la práctica correcta para cualquier cliente.

## Documentación interactiva (Swagger)

Con `APP_ENV=local` y el servidor encendido:

| Recurso | URL |
|---|---|
| Interfaz interactiva | <http://localhost:8000/api/v1/swagger> |
| Especificación OpenAPI 3.1 (JSON) | <http://localhost:8000/api/v1/swagger.json> |

**Cómo probar los endpoints desde la interfaz:**

1. Abre `POST /v1/auth/login`, envía las credenciales del *seeder* y copia el `access_token`.
2. En cualquier endpoint protegido, pega el token en el campo de autenticación *Bearer*.
3. Usa *Send API Request* para ejecutar la petición.

**Solo en desarrollo.** Las rutas de la documentación se registran únicamente cuando `APP_ENV=local`. En cualquier otro entorno no existen y responden 404, de modo que en producción no queda expuesta la estructura de la API. Hay una prueba automatizada que lo verifica.

**Cómo se genera.** No hay un archivo Swagger escrito a mano. Scramble analiza el código cada vez que se consulta la URL:

| Lo que Scramble lee | Lo que documenta |
|---|---|
| El comentario PHPDoc de cada método del controlador | Título y descripción del endpoint |
| Las reglas de los Form Requests | Cuerpo de la petición, tipos, campos obligatorios, límites y la respuesta 422 |
| `$request->validate([...])` dentro del controlador | Parámetros de consulta (por ejemplo `per_page`) |
| El método `toArray()` de cada Resource | Estructura de la respuesta |
| `->setStatusCode(201)` y `response()->noContent()` | Códigos 201 y 204 |
| El *route model binding* (`{product}`) | Parámetro de ruta y respuesta 404 |
| El middleware `auth:sanctum` | Respuesta 401 y el candado *Bearer* |
| El atributo `#[Response(409, ...)]` | La respuesta 409, que no se puede inferir porque la lanza el servicio |

Por eso la documentación nunca se desfasa del código.

**Exportar la especificación OpenAPI a un archivo:**

```bash
php artisan scramble:export --path=docs/openapi.json
```

Cualquier herramienta compatible con OpenAPI puede importar ese archivo (o la URL `/api/v1/swagger.json`). Para Postman ya existe una colección lista y probada; está descrita en la sección siguiente.

> La interfaz carga la librería Stoplight Elements desde un CDN, así que necesita conexión a internet.

## Colección de Postman

El archivo [`docs/postman/laravel-store.postman_collection.json`](docs/postman/laravel-store.postman_collection.json) (formato *Collection v2.1*) contiene las 9 peticiones de la API, ordenadas para ejecutarse de principio a fin sin configurar nada a mano.

### Cómo importarla

1. Abre Postman y pulsa **Import** (arriba a la izquierda).
2. Arrastra el archivo `docs/postman/laravel-store.postman_collection.json` o selecciónalo desde tu disco.
3. Confirma con **Import**. En el panel izquierdo aparece la colección **Laravel Store**.

Si ya tenías importada una versión anterior, Postman pregunta si quieres reemplazarla o importarla como copia. Elige **Replace** para quedarte con la versión del repositorio.

### Variables de la colección

Se ven y se editan haciendo clic en la colección y abriendo la pestaña **Variables**.

| Variable | Valor inicial | Quién la llena | Para qué sirve |
|---|---|---|---|
| `base_url` | `http://localhost:8000/api` | Tú, solo si la API corre en otra dirección | Dirección base de todas las peticiones. Incluye `/api`; las rutas de la colección agregan `/v1/...` |
| `access_token` | vacío | La petición **Login**, automáticamente | Token Bearer que usan todas las demás peticiones |
| `product_id` | vacío | La petición **Create Product**, automáticamente | ID del producto sobre el que trabajan Show, Update, Prices y Delete |

### Estructura y orden de ejecución

```
Laravel Store
├── Login                          POST   /v1/auth/login                     → guarda {{access_token}}
├── Products
│   ├── Get Products               GET    /v1/products?page=1&per_page=10
│   ├── Create Product             POST   /v1/products                       → guarda {{product_id}}
│   ├── Show Product               GET    /v1/products/{{product_id}}
│   ├── Update Product             PUT    /v1/products/{{product_id}}
│   ├── Prices
│   │   ├── Create Product Prices  POST   /v1/products/{{product_id}}/prices
│   │   └── Get Product Prices     GET    /v1/products/{{product_id}}/prices
│   └── Delete Product             DELETE /v1/products/{{product_id}}
└── Logout                         POST   /v1/auth/logout                    → revoca el token
```

El orden no es casual:

- **Login va primero** porque todas las demás rutas exigen token.
- **Prices está dentro de Products**, igual que en la API (`/products/{id}/prices`), y **antes de Delete Product**: un producto eliminado responde 404, así que ya no aceptaría precios.
- **Logout va al final** para cerrar la sesión una vez terminado el recorrido.

### Cómo funciona

**Autenticación automática.** La pestaña *Scripts → Post-response* de **Login** contiene un script que, si la respuesta es 200, guarda el `access_token` en la variable de la colección. Todas las demás peticiones tienen configurada en *Authorization* la opción *Bearer Token* con el valor `{{access_token}}`, así que no hay que copiar ni pegar el token. Si el token expira (24 horas) o se ejecutó Logout, basta con volver a ejecutar Login.

**Producto automático.** **Create Product** guarda el `id` del producto recién creado en `{{product_id}}`, y las peticiones siguientes trabajan sobre ese producto. Como cada ejecución crea un producto nuevo, la colección se puede correr las veces que quieras sin chocar con el 409 de "precio duplicado". Al final, **Delete Product** elimina ese producto (soft delete), así que no se acumulan datos de prueba visibles.

**Verificación incluida.** Cada petición tiene una prueba que comprueba el código de estado esperado (200, 201 o 204). El resultado aparece en la pestaña **Test Results** de la respuesta.

**Datos de ejemplo.** **Create Product** usa `currency_id: 1` como divisa base y **Create Product Prices** usa `currency_id: 3`. Ambas existen después de `php artisan migrate --seed`, que crea cinco divisas. Si el precio responde 422, revisa que la divisa del precio sea distinta de la divisa base del producto.

### Ejecutar la colección completa

**Desde Postman:**

1. Levanta la API (`php artisan serve`) con la base de datos sembrada.
2. Abre el menú `⋯` de la colección **Laravel Store** y elige **Run collection**.
3. Pulsa **Run Laravel Store**.

El resultado esperado es **9 peticiones y 9 pruebas aprobadas**.

Si prefieres ejecutar las peticiones una por una, empieza siempre por **Login** y ejecuta **Create Product** antes de Show, Update, Prices o Delete.

**Desde la terminal (Postman CLI):**

```bash
postman collection run docs/postman/laravel-store.postman_collection.json
```

Si la API corre en otra dirección, sobrescribe la variable sin editar el archivo:

```bash
postman collection run docs/postman/laravel-store.postman_collection.json --env-var "base_url=http://127.0.0.1:9000/api"
```

> Postman CLI puede mostrar al final un aviso pidiendo `postman login`. Ese inicio de sesión solo se usa para publicar los resultados en la nube de Postman; la ejecución local funciona sin él.

### Insomnia

Insomnia también importa este archivo (**Import** → **From File**). Los scripts de Postman pueden no ejecutarse en Insomnia. Si el token no se guarda solo, copia el `access_token` de la respuesta de Login en la variable `access_token`, y haz lo mismo con el `id` de Create Product en `product_id`.

## Endpoints

URL base: `http://localhost:8000/api/v1`

| Método | Ruta | Descripción | Token | Respuestas |
|---|---|---|---|---|
| `POST` | `/auth/login` | Iniciar sesión y obtener un token | No | 200, 401, 422, 429 |
| `POST` | `/auth/logout` | Cerrar sesión (revoca el token actual) | Sí | 204, 401 |
| `GET` | `/products` | Obtener lista de productos (paginada) | Sí | 200, 401, 422 |
| `POST` | `/products` | Crear un nuevo producto | Sí | 201, 401, 422 |
| `GET` | `/products/{id}` | Obtener un producto por ID, con sus precios | Sí | 200, 401, 404 |
| `PUT` | `/products/{id}` | Actualizar un producto | Sí | 200, 401, 404, 422 |
| `DELETE` | `/products/{id}` | Eliminar un producto (soft delete) | Sí | 204, 401, 404 |
| `GET` | `/products/{id}/prices` | Obtener lista de precios de un producto | Sí | 200, 401, 404 |
| `POST` | `/products/{id}/prices` | Crear un nuevo precio para un producto | Sí | 201, 401, 404, 409, 422 |

### Parámetros y reglas de validación

**`GET /products`**

| Parámetro | Tipo | Regla |
|---|---|---|
| `per_page` | entero, opcional | Entre 1 y 100. Por defecto 10 |
| `page` | entero, opcional | Página a consultar. Por defecto 1 |

Los productos se devuelven del más reciente al más antiguo.

**`POST /products`**

| Campo | Regla |
|---|---|
| `name` | Obligatorio, texto, máximo 255 caracteres |
| `description` | Obligatorio, texto (el enunciado exige que todo producto tenga descripción) |
| `price` | Obligatorio, número con hasta 2 decimales, entre 0 y 9,999,999,999.99 |
| `currency_id` | Obligatorio, ID de una divisa existente (divisa base del producto) |
| `tax_cost` | Obligatorio, número con hasta 2 decimales, entre 0 y 9,999,999,999.99 |
| `manufacturing_cost` | Obligatorio, número con hasta 2 decimales, entre 0 y 9,999,999,999.99 |

**`PUT /products/{id}`**

La actualización es **parcial**: solo se modifican los campos enviados. Acepta los mismos campos que la creación, todos opcionales, con estas reglas:

| Campo | Regla |
|---|---|
| `name` | Texto, máximo 255 caracteres |
| `description` | Texto |
| `price`, `tax_cost`, `manufacturing_cost` | Número con hasta 2 decimales, entre 0 y 9,999,999,999.99 (las mismas reglas que al crear) |
| `currency_id` | ID de una divisa existente **en la que el producto no tenga un precio registrado** |

Un monto con más de dos decimales se rechaza con 422; nunca se redondea en silencio.

**`POST /products/{id}/prices`**

| Campo | Regla |
|---|---|
| `currency_id` | Obligatorio, ID de una divisa existente y **distinta a la divisa base del producto** |
| `price` | Obligatorio, número con hasta 2 decimales, entre 0 y 9,999,999,999.99 |

Un producto solo puede tener **un precio por divisa**. Intentar registrar un segundo precio en la misma divisa responde **409 Conflict**.

## Formato de respuestas y errores

### Respuestas exitosas

Los datos siempre vienen dentro de la llave `data`. Los listados paginados agregan `links` y `meta` con la información de paginación.

**Producto** (`POST /products` → 201, con el encabezado `Location: http://localhost:8000/api/v1/products/1`):

```json
{
    "data": {
        "id": 1,
        "name": "Laptop Pro 14",
        "description": "Laptop de 14 pulgadas",
        "symbol": "$",
        "price": "1200.00",
        "taxCost": "192.00",
        "manufacturingCost": "650.00",
        "createdAt": "24/10/2026 10:00:00",
        "updatedAt": "24/10/2026 10:00:00"
    },
    "message": "Product created successfully."
}
```

`GET /products/{id}` devuelve la misma estructura (sin `message`) e incluye además el arreglo `prices`.

**Precio de un producto** (`GET /products/{id}/prices` → 200):

```json
{
    "data": [
        {
            "id": 1,
            "name": "Laptop Pro 14",
            "price": "999.00",
            "baseExchangeRate": "1.000000",
            "exchangeRate": "0.850000",
            "symbol": "€",
            "convertedPrice": "1020.00",
            "createdAt": "24/10/2026 10:00:00",
            "updatedAt": "24/10/2026 10:00:00"
        }
    ]
}
```

| Campo | Significado |
|---|---|
| `name` | Nombre del producto al que pertenece el precio |
| `price` | Precio registrado manualmente en esa divisa |
| `baseExchangeRate` | Tasa de cambio de la divisa base del producto |
| `exchangeRate` | Tasa de cambio de la divisa de este precio |
| `symbol` | Símbolo de la divisa de este precio |
| `convertedPrice` | Precio base del producto convertido a esta divisa con las tasas de cambio (ver [Conversión de divisas](#conversión-de-divisas)) |

**Convenciones:**

- Los montos se devuelven como **texto con dos decimales** (`"1200.00"`) para no perder precisión, como ocurriría con números de punto flotante.
- Las fechas usan el formato `dd/mm/aaaa hh:mm:ss`, en horario UTC.

### Errores

Todos los errores responden en JSON con la llave `message`. Los errores de validación agregan `errors`, con el detalle por campo.

| Código | Cuándo ocurre | Ejemplo de cuerpo |
|---|---|---|
| **401** Unauthorized | Falta el token, es inválido o expiró | `{"message": "Unauthenticated."}` |
| **401** Unauthorized | Correo o contraseña incorrectos en el login | `{"message": "Invalid credentials"}` |
| **404** Not Found | El producto no existe o fue eliminado | `{"message": "The requested resource was not found."}` |
| **404** Not Found | La URL no existe | `{"message": "The requested URL was not found on the server. ..."}` |
| **409** Conflict | El producto ya tiene un precio en esa divisa | `{"message": "The product already has a price for the specified currency."}` |
| **422** Unprocessable Content | Los datos enviados no pasan la validación | Ver abajo |
| **429** Too Many Requests | Se superó el límite de peticiones | `{"message": "Too Many Attempts."}` |

Ejemplo de error de validación (422):

```json
{
    "message": "The name field is required. (and 5 more errors)",
    "errors": {
        "name": ["The name field is required."],
        "description": ["The description field is required."],
        "price": ["The price field must be at least 0."],
        "currency_id": ["The currency id field is required."],
        "tax_cost": ["The tax cost field is required."],
        "manufacturing_cost": ["The manufacturing cost field is required."]
    }
}
```

## Conversión de divisas

### Qué significa `exchange_rate`

El enunciado no define contra qué moneda se expresa la tasa de cambio, así que se tomó esta convención:

> `exchange_rate` indica **cuántas unidades de esa divisa equivalen a 1 unidad de la divisa base** (dólar estadounidense = 1).

Por ejemplo, euro = 0.85 significa que 1 USD = 0.85 EUR.

### Fórmula

La divisa del producto no siempre es la divisa base, así que la conversión pasa siempre por ella:

```
precio_convertido = precio_del_producto ÷ tasa_de_la_divisa_del_producto × tasa_de_la_divisa_destino
```

| Caso | Cálculo | Resultado |
|---|---|---|
| 1,200.00 USD a EUR | 1200 ÷ 1 × 0.85 | **1,020.00 EUR** |
| 10.10 USD a EUR | 10.10 ÷ 1 × 0.85 = 8.585 | **8.59 EUR** (redondeo hacia arriba en el medio centavo) |
| 100.00 EUR a JPY | 100 ÷ 0.85 × 110 = 12,941.176… | **12,941.18 JPY** |

### Cómo se calcula

- El cálculo vive en el método `Currency::convert()`, porque la divisa es la que sabe convertir montos.
- Se usa la extensión **`bcmath`**, que opera con decimales exactos representados como texto. Con números de punto flotante, operaciones como `0.1 + 0.2` dan `0.30000000000000004`, y en dinero eso termina en centavos perdidos.
- El resultado se redondea a 2 decimales *half-up* (el medio centavo sube).
- La conversión **se calcula al consultar** y no se guarda. Si cambia la tasa de una divisa, la siguiente consulta ya refleja el valor nuevo.
- El campo `price` de cada precio es el valor que alguien registró manualmente (por ejemplo, 999.00 EUR como precio comercial). `convertedPrice` es la referencia matemática. Mostrar ambos permite comparar el precio comercial contra la conversión exacta.

## Modelo de datos

```
┌──────────────────────┐        ┌───────────────────────┐        ┌──────────────────────┐
│ products             │        │ product_prices        │        │ currencies           │
├──────────────────────┤        ├───────────────────────┤        ├──────────────────────┤
│ id                   │◄───────┤ product_id (FK)       │        │ id                   │
│ name                 │  1   N │ currency_id (FK)      ├───────►│ name                 │
│ description          │        │ price                 │  N   1 │ symbol               │
│ price                │        │ UNIQUE(product_id,    │        │ exchange_rate        │
│ currency_id (FK) ────┼────────┼──► currency_id)       │        └──────────────────────┘
│ tax_cost             │        └───────────────────────┘                  ▲
│ manufacturing_cost   │                                                   │
│ deleted_at (null)    │───────────────────────────────────────────────────┘
└──────────────────────┘                    divisa base del producto (N:1)
```

| Tabla | Campo | Tipo | Notas |
|---|---|---|---|
| `currencies` | `exchange_rate` | `decimal(18,6)` | Seis decimales para tasas pequeñas |
| `products` | `price`, `tax_cost`, `manufacturing_cost` | `decimal(12,2)` | Nunca `float`: el dinero exige exactitud |
| `products` | `description` | `text`, NOT NULL | Obligatoria, igual que en la validación de la API |
| `products` | `currency_id` | FK → `currencies` | `restrictOnDelete`: no se puede borrar una divisa en uso |
| `products` | `deleted_at` | `timestamp`, nullable | Soft delete |
| `product_prices` | `product_id` | FK → `products` | `cascadeOnDelete` (solo aplica en un borrado físico) |
| `product_prices` | `currency_id` | FK → `currencies` | `restrictOnDelete` |
| `product_prices` | `(product_id, currency_id)` | índice único | Garantiza un precio por divisa directamente en la base de datos |

Además están las tablas de soporte de Laravel, entre ellas `users`, `personal_access_tokens` (tokens de Sanctum) y `cache` (que usa el limitador de peticiones).

## Arquitectura y organización del código

### Recorrido de una petición

```
Petición HTTP
   │
   ▼
routes/api.php ──────────► middleware auth:sanctum (¿token válido?) + throttle (¿límite superado?)
   │
   ▼
Form Request ────────────► valida los datos; si fallan responde 422 sin llegar al controlador
   │
   ▼
Controller ──────────────► traduce HTTP: recibe datos validados, llama al servicio, elige el código de estado
   │
   ▼
ProductService ──────────► reglas de negocio; no sabe nada de HTTP
   │
   ▼
Modelos Eloquent ────────► relaciones, casts, soft delete, conversión de divisas
   │                         └─ ProductObserver registra en el log cada alta, cambio y baja
   ▼
MySQL

Respuesta ◄── Resource (da forma al JSON)
Errores   ◄── bootstrap/app.php (convierte excepciones en 404 / 409 con JSON)
```

### Estructura de carpetas

```
app/
├── Exceptions/
│   └── DuplicateProductPrice.php      Excepción de negocio: precio duplicado en una divisa
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php             Clase base del esqueleto de Laravel
│   │   ├── AuthController.php         Login y logout
│   │   ├── ProductController.php      CRUD de productos
│   │   └── ProductPriceController.php Listado y alta de precios por producto
│   ├── Requests/
│   │   ├── LoginRequest.php
│   │   ├── StoreProductRequest.php
│   │   ├── UpdateProductRequest.php
│   │   └── StoreProductPriceRequest.php
│   └── Resources/
│       ├── ProductResource.php
│       ├── ProductPriceResource.php
│       └── CurrencyResource.php
├── Models/
│   ├── Currency.php
│   ├── Product.php
│   ├── ProductPrice.php
│   └── User.php
├── Observers/
│   └── ProductObserver.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    └── ProductService.php
bootstrap/app.php                      Rutas, middleware y manejo centralizado de errores
config/scramble.php                    Configuración de la documentación Swagger
docs/postman/                          Colección de Postman lista para importar
database/
├── factories/                         Datos falsos para pruebas
├── migrations/                        Estructura de las tablas
└── seeders/                           Datos iniciales (usuario y divisas)
routes/api.php                         Definición de endpoints
tests/
├── Feature/                           Pruebas de cada endpoint vía HTTP
└── Unit/                              Pruebas de lógica pura (conversión de divisas)
docker-compose.yml                     MySQL 8.4 y Adminer para desarrollo
```

### Rutas — `routes/api.php`

- Todas las rutas viven bajo el prefijo **`/api/v1`**. Versionar desde el inicio permite publicar una `v2` sin romper a los clientes actuales.
- `POST /auth/login` es la única ruta pública y tiene su propio limitador (`throttle:login`).
- Todo lo demás está dentro de un grupo con `auth:sanctum` (exige token) y `throttle:api` (límite general).
- Se usa `Route::apiResource()`, que genera las rutas REST estándar con nombre (`products.index`, `products.show`, etc.) y con *route model binding*.
- Los precios se definen como **recurso anidado** (`products.prices`), limitado a `index` y `store`, que son los únicos que pide el enunciado.

### Providers — `app/Providers/AppServiceProvider.php`

El *service provider* es el punto donde Laravel configura servicios al arrancar la aplicación. Aquí se registran dos cosas:

**1. Limitadores de peticiones (rate limiting)**

| Limitador | Regla | Por qué |
|---|---|---|
| `login` | 5 intentos por minuto por combinación de **correo + IP**, y 20 por minuto por **IP** | Evita ataques de fuerza bruta contra una cuenta, sin bloquear a usuarios distintos que comparten la misma IP (por ejemplo, una oficina) |
| `api` | 60 peticiones por minuto por **usuario** (o por IP si no hay usuario) | Protege la API de abusos y de clientes con errores que repiten peticiones en bucle |

Al superar el límite se responde **429** con el encabezado `Retry-After`. Desde Laravel 11 estos limitadores ya no vienen predefinidos, por eso se declaran aquí.

**2. Documentación Swagger (Scramble)**

- `routes()` indica qué rutas se documentan: todas las de `api/v1/`, excepto las de la propia documentación.
- `expose()` publica la interfaz en `/api/v1/swagger` y el JSON en `/api/v1/swagger.json`, **solo si `APP_ENV=local`**. Fuera de local las rutas no se registran.

### Manejo de errores — `bootstrap/app.php`

Desde Laravel 11, el manejo de excepciones se configura en este archivo. Se definió:

- **`shouldRenderJsonWhen`**: toda ruta bajo `api/*` responde errores en JSON, aunque el cliente no envíe `Accept: application/json`. Nunca se devuelve una página HTML de error.
- **404 personalizado**: por defecto Laravel responde `No query results for model [App\Models\Product] 6`, lo que revela el nombre interno de la clase incluso con `APP_DEBUG=false`. Se reemplaza por un mensaje genérico y se distingue entre "el recurso no existe" y "la URL no existe".
- **409 para `DuplicateProductPrice`**: la excepción de negocio se traduce aquí al código HTTP. Así el servicio no depende de HTTP y todos los mapeos de errores quedan en un solo lugar.
- **`dontReport(DuplicateProductPrice::class)`**: un precio duplicado es una regla de negocio que funcionó bien, no un fallo. No se escribe en el log de errores, para no ensuciar el monitoreo.

### Controllers — `app/Http/Controllers`

Los controladores son **delgados**: solo traducen entre HTTP y la aplicación.

1. Reciben datos ya validados por el Form Request.
2. Llaman al servicio.
3. Envuelven el resultado en un Resource y eligen el código de estado: 201 con encabezado `Location` al crear, 204 al eliminar.

No contienen reglas de negocio ni consultas complejas. El servicio llega por **inyección de dependencias en el constructor** (`private readonly ProductService $products`), y el contenedor de Laravel lo resuelve automáticamente.

`show()` no pasa por el servicio: el *route model binding* ya buscó el producto (o respondió 404), y envolverlo en un método del servicio solo pasaría el dato de un lado a otro.

### Form Requests — `app/Http/Requests`

Cada operación de escritura tiene su clase de validación. Así el controlador recibe datos ya limpios y las reglas se reutilizan y documentan solas en Swagger.

| Clase | Endpoint | Detalle relevante |
|---|---|---|
| `LoginRequest` | `POST /auth/login` | Correo con formato válido y contraseña obligatoria |
| `StoreProductRequest` | `POST /products` | `decimal:0,2` impide más de dos decimales; `max:9999999999.99` coincide con la columna `decimal(12,2)` y evita un error 500 por desbordamiento en MySQL |
| `UpdateProductRequest` | `PUT /products/{id}` | Reglas con `sometimes`: solo se validan los campos enviados. `Rule::notIn([divisas con precio])` impide cambiar la divisa base a una en la que el producto ya tiene precio registrado |
| `StoreProductPriceRequest` | `POST /products/{id}/prices` | `Rule::notIn([moneda base])` impide registrar un precio en la divisa base, que ya está en `products.price` |

Solo los campos validados (`$request->validated()`) llegan al modelo, así que cualquier campo extra que mande el cliente se descarta.

### Services — `app/Services/ProductService.php`

El servicio concentra la lógica de negocio de productos y precios, separada de HTTP. No recibe `Request` ni devuelve respuestas HTTP, así que podría reutilizarse igual desde un comando de consola, un *job* o una prueba.

| Método | Qué hace |
|---|---|
| `list(int $perPage)` | Pagina los productos con su divisa precargada (`with('currency')`) para evitar el problema N+1 |
| `create(array $data)` | Crea el producto y carga su divisa para la respuesta |
| `update(Product $product, array $data)` | Actualiza solo los campos recibidos |
| `delete(Product $product)` | Elimina con soft delete |
| `addPrice(Product $product, array $data)` | Registra un precio en otra divisa; si ya existe uno en esa divisa, lanza `DuplicateProductPrice` |

**Por qué `addPrice` usa `try/catch` y no "consultar primero si existe":** si dos peticiones llegan al mismo tiempo, ambas podrían pasar la consulta previa y la segunda fallaría con un error 500. El índice único de la base de datos es la única garantía real. Se intenta insertar y, si la base rechaza el duplicado (`UniqueConstraintViolationException`), se traduce a la excepción de negocio.

**Por qué no hay `DB::transaction`:** cada operación hace una sola escritura, que ya es atómica. Las transacciones se reservan para cuando hay varias escrituras que deben aplicarse todas o ninguna.

**Por qué el servicio es `final` y no tiene interfaz:** existe una sola implementación. Laravel inyecta la clase concreta sin configuración, y crear una interfaz para una única implementación agrega código sin aportar nada. Si en el futuro hubiera una segunda implementación, ese sería el momento de introducirla.

### Excepciones de negocio — `app/Exceptions/DuplicateProductPrice.php`

Representa la regla "un producto no puede tener dos precios en la misma divisa". Extiende de `RuntimeException`, trae su mensaje por defecto y **no sabe nada de HTTP**. El código 409 se asigna en `bootstrap/app.php`.

### Resources — `app/Http/Resources`

Los *API Resources* definen exactamente qué campos salen en cada respuesta. Así el JSON no depende de la estructura interna de la tabla: nunca se filtran columnas como `deleted_at`, y el formato de fechas y montos se controla en un solo lugar.

| Resource | Representa | Campos |
|---|---|---|
| `ProductResource` | Un producto | `id`, `name`, `description`, `symbol` (de su divisa base), `price`, `taxCost`, `manufacturingCost`, `prices` (solo si se cargaron), `createdAt`, `updatedAt` |
| `ProductPriceResource` | Un precio en otra divisa | `id`, `name` (del producto), `price`, `baseExchangeRate`, `exchangeRate`, `symbol`, `convertedPrice`, `createdAt`, `updatedAt` |
| `CurrencyResource` | Una divisa | `id`, `name`, `symbol`. Preparado para un futuro endpoint de divisas; hoy no lo usa ningún controlador |

`ProductResource` usa `whenLoaded('prices')`: los precios aparecen solo si el controlador los cargó. Por eso `GET /products/{id}` los incluye y el listado no, lo que mantiene el listado liviano.

### Modelos — `app/Models`

| Modelo | Relaciones | Detalles |
|---|---|---|
| `Product` | `currency()` → divisa base · `prices()` → precios en otras divisas | `SoftDeletes`, casts `decimal:2` en montos y observador `ProductObserver` |
| `ProductPrice` | `product()` · `currency()` | `product_id` no es asignable de forma masiva: solo lo asigna la relación, así que un cliente no puede asignar el precio a otro producto |
| `Currency` | `products()` · `productPrices()` | Cast `decimal:6` en `exchange_rate` y método `convert()` |
| `User` | Tokens de Sanctum (`HasApiTokens`) | La contraseña se cifra automáticamente con el cast `hashed` |

Detalles que vale la pena explicar:

- **Casts `decimal:2`**: los montos siempre salen como texto con dos decimales (`"1200.00"`), sin importar el motor de base de datos. Sin el cast, SQLite devuelve números y MySQL textos, y el contrato de la API cambiaría según el entorno.
- **`->chaperone()` en `Product::prices()`**: cada precio recibe una referencia al mismo objeto producto que lo cargó. `ProductPriceResource` necesita el producto para calcular `convertedPrice`, y sin `chaperone()` haría una consulta extra por cada precio (problema N+1).
- **`product_prices` es un modelo propio y no una tabla pivote**: aunque une productos con divisas, tiene identidad (`id`), endpoints propios y un dato de negocio (`price`). Como modelo, crear un duplicado responde 409 de forma explícita; con `belongsToMany`, un `syncWithoutDetaching()` lo habría sobrescrito en silencio.

### Observers — `app/Observers/ProductObserver.php`

El observador escucha los eventos del modelo `Product` y deja un registro de auditoría en `storage/logs/laravel.log`:

| Evento | Registro |
|---|---|
| `created` | `Product created` con el `id` |
| `updated` | `Product updated` con el `id` y los campos que cambiaron (`getChanges()`) |
| `deleted` | `Product deleted` con el `id` (incluye el soft delete) |

Se conecta al modelo con el atributo `#[ObservedBy(ProductObserver::class)]`.

**Por qué en un observador y no en el servicio:** la auditoría es una preocupación transversal. El observador la aplica sin importar desde dónde se modifique el producto (API, consola o *tinker*) y mantiene el servicio enfocado en el negocio.

**Limitación:** los eventos no se disparan en operaciones masivas como `Product::where(...)->update([...])`, solo al guardar o borrar una instancia.

### Migraciones — `database/migrations`

Las reglas de integridad se aplican **en la base de datos**, no solo en la aplicación:

- **Llaves foráneas con `restrictOnDelete`** hacia `currencies`: no se puede borrar una divisa que usa algún producto o precio.
- **`cascadeOnDelete`** de `product_prices` hacia `products`: si un producto se borra físicamente, sus precios se van con él.
- **Índice único** `(product_id, currency_id)`: un precio por divisa, garantizado incluso con peticiones simultáneas.
- **Columnas `decimal`** para todo lo monetario.
- **Comentarios en cada columna** (`->comment()`), visibles desde Adminer o cualquier cliente SQL.

### Factories y Seeders — `database/factories`, `database/seeders`

- **Factories** (`ProductFactory`, `ProductPriceFactory`, `CurrencyFactory`, `UserFactory`): generan datos de prueba. Cada una crea automáticamente sus dependencias; por ejemplo, `ProductPriceFactory` crea su producto y su divisa si no se le pasan. Así cada prueba arma sus propios datos en una línea.
- **Seeders**: `UserSeeder` crea el usuario `root@example.com` y `CurrencySeeder` crea cinco divisas. `DatabaseSeeder` los ejecuta en orden.

### Herramientas incluidas por Laravel

- **`/up`**: ruta de *health check* nativa, útil para balanceadores y orquestadores.
- **Laravel Pint**: formatea el código. Para revisarlo sin modificar nada: `vendor/bin/pint --test`.

## Pruebas automatizadas

```bash
php artisan test
```

Las pruebas usan **SQLite en memoria** (configurado en `phpunit.xml`), así que no necesitan Docker ni tocan la base de desarrollo. Cada prueba arranca con la base vacía (`RefreshDatabase`) y crea sus propios datos con *factories*.

Los controladores se prueban con **pruebas de feature**: hacen peticiones HTTP reales a la aplicación, de modo que se verifican juntos las rutas, el middleware, la validación, el servicio, la base de datos y el formato de la respuesta. Se evitan los *mocks* a propósito: un *mock* escondería justo lo que importa, como el índice único que produce el 409.

| Archivo | Pruebas | Qué verifica |
|---|---|---|
| `tests/Feature/AuthControllerTest.php` | 7 | Login correcto con token Bearer, un solo token activo por usuario, contraseña incorrecta (401), validación (422), **bloqueo al sexto intento (429)**, logout revoca el token, logout sin token (401) |
| `tests/Feature/ProductControllerTest.php` | 18 | 401 en los 5 endpoints sin token, paginación y orden, `per_page` inválido, creación con 201 y `Location`, descripción obligatoria (422 sin guardar nada), campos obligatorios, montos y divisa inválidos, detalle con precios, 404, actualización parcial, en la actualización: monto negativo, **más de dos decimales sin redondear**, monto fuera del límite de la columna, monto justo en el límite, divisa inexistente, **divisa base que ya tiene precio (422)** y divisa base libre (200), **soft delete** |
| `tests/Feature/ProductPriceControllerTest.php` | 7 | 401 sin token, listado con `convertedPrice` correcto, 404 con producto inexistente o eliminado, creación, **409 por divisa duplicada**, 422 por divisa base, validación |
| `tests/Unit/CurrencyConvertTest.php` | 3 | Conversión con la misma tasa, redondeo del medio centavo hacia arriba (8.585 → 8.59) y conversión entre dos divisas que no son la base |
| `tests/Feature/ApiDocsTest.php` | 1 | Swagger responde 404 fuera del entorno local |

Total: **36 pruebas**.

## Seguridad

| Medida | Cómo se implementa |
|---|---|
| Autenticación | Tokens Bearer de Laravel Sanctum. Todas las rutas, excepto el login, exigen token |
| Expiración de tokens | 24 horas (`config/sanctum.php`) |
| Una sesión activa por usuario | Cada login revoca el token anterior del usuario |
| Cierre de sesión real | `logout` elimina el token de la base de datos; no basta con olvidarlo en el cliente |
| Fuerza bruta en el login | 5 intentos por minuto por correo + IP y 20 por IP |
| Abuso de la API | 60 peticiones por minuto por usuario |
| Validación estricta | Form Requests en cada escritura; solo los campos validados llegan al modelo |
| Asignación masiva | `$fillable` explícito en cada modelo; `product_id` solo lo asigna la relación |
| Contraseñas | Cifradas automáticamente (cast `hashed`, bcrypt) |
| Errores sin filtrar información | Siempre en JSON y sin el nombre interno de las clases en los 404. En producción debe usarse `APP_DEBUG=false` para ocultar trazas |
| Inyección SQL | Toda consulta pasa por Eloquent con parámetros enlazados; no hay SQL armado concatenando texto |
| Documentación | Swagger solo existe en `APP_ENV=local` |
| Base de datos | MySQL publicado solo en `127.0.0.1`, inaccesible desde otras máquinas de la red |
| Integridad | Llaves foráneas, índice único y tipos `decimal` en la base de datos |

## Decisiones de diseño y supuestos

1. **Divisa base y `exchange_rate`.** Cada producto tiene una divisa base (`currency_id`) y su `price` está expresado en ella. Las tasas se miden contra el dólar estadounidense (= 1), según la convención descrita en [Conversión de divisas](#conversión-de-divisas).
2. **Precios manuales más conversión calculada.** El enunciado pide "registrar precios en diferentes divisas", así que `product_prices` guarda precios definidos por una persona. La conversión matemática se calcula al consultar y se muestra junto al precio registrado, sin guardarse.
3. **No se permite un precio en la divisa base.** Ese valor ya está en `products.price`; duplicarlo crearía dos fuentes de verdad que podrían contradecirse (422). La regla se aplica en ambos sentidos: no se puede registrar un precio en la divisa base, y un `PUT` tampoco puede cambiar la divisa base a una en la que el producto ya tiene precio.
4. **Un precio por divisa (409).** Lo garantiza el índice único de la base de datos. Se responde 409 Conflict y no 422, porque los datos son válidos pero chocan con el estado actual del recurso.
5. **Soft delete.** Eliminar un producto lo oculta de todas las consultas, pero conserva el registro y su historial de precios para auditoría.
6. **`PUT` con actualización parcial.** Se aceptan solo los campos que cambian, lo que simplifica a los clientes. `PATCH` funciona igual.
7. **Arquitectura en capas sin sobreingeniería.** Controller, Form Request, Service, Model y Resource. No hay patrón *Repository*: Eloquent ya cumple ese papel y el enunciado pide usarlo directamente. Tampoco hay interfaces con una sola implementación.
8. **Documentación generada desde el código.** Scramble produce el OpenAPI a partir de las validaciones y los Resources, así que la documentación no puede quedar desactualizada respecto a la implementación.
9. **Versionado desde el día uno.** El prefijo `/api/v1` permite evolucionar la API sin romper a los clientes existentes.
