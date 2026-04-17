# Documentazione Web Service — PC Builder

## Descrizione sintetica della realtà di riferimento

Il progetto è un sistema per la **configurazione personalizzata di computer (PC Builder)**. L'applicazione permette di:

- Consultare un catalogo di **componenti hardware** (CPU, GPU, RAM, ecc.) organizzati per categoria e produttore.
- **Creare e gestire build** (configurazioni PC) personalizzate, aggiungendo componenti con verifica automatica della compatibilità.
- Gestire **categorie** di componenti con le relative specifiche tecniche (es. socket, frequenza).
- Gestire i **produttori** dei componenti.
- Autenticarsi tramite **JWT** (cookie `token`) con due livelli di accesso: utente normale e owner (amministratore).

---

## Web API

### URL del Web Service

```
http://localhost/
```

La documentazione interattiva (Swagger UI) è disponibile all'indirizzo:

```
http://localhost/docs/
```

---

### Script PHP di gestione (Entry Points)

Tutti gli indirizzi vengono riscritti da Apache (mod_rewrite) verso un unico entry point:

| Script | Quando viene richiamato |
|---|---|
| `index.php` | Viene richiamato per **ogni richiesta HTTP** al web service. Carica la configurazione da `config/config.yaml`, inizializza il `Router` e lo `FileHandler`, e avvia la gestione della richiesta. |

Il `Router` (`framework/Router.php`) riceve la richiesta da `index.php`, individua il controller corretto in base a metodo HTTP e percorso URI, applica i middleware di autorizzazione e delega l'esecuzione al controller appropriato.

---

### Struttura delle directory

```
/
├── index.php                  ← entry point unico (eseguito dal web server)
├── .htaccess                  ← rewrite engine: tutte le richieste → index.php
├── config/
│   └── config.yaml            ← configurazione (debug, hosts, path)
├── app/
│   ├── authorization/         ← codice di supporto: gestione JWT e auth
│   │   └── Authorization.php
│   ├── middlewares/           ← middleware di autenticazione
│   │   ├── AuthMiddleware.php
│   │   └── OwnerAuthMiddleware.php
│   └── routes/                ← controller specifici per ogni risorsa
│       ├── builds/
│       ├── categories/
│       ├── components/
│       ├── manufacturers/
│       └── profile/
├── framework/                 ← codice generale (Router, Request, Response, ecc.)
└── db.sql                     ← file SQL per la creazione del database
```

---

### Livelli di autorizzazione

Il web service implementa due livelli di accesso, verificati tramite cookie JWT (`token`):

| Middleware | Requisito | Risposta in caso di errore |
|---|---|---|
| *(nessuno)* | Accesso libero | — |
| `AuthMiddleware` | Utente autenticato (login effettuato) | `401 Unauthorized` |
| `OwnerAuthMiddleware` | Utente autenticato **e** con flag `is_owner = true` | `401` se non loggato, `403 Forbidden` se loggato ma non owner |

---

### Risorse gestite

Il web service espone le seguenti risorse:

| Risorsa | URI base | Descrizione |
|---|---|---|
| **Users / Profile** | `/api/profile`, `/api/login`, `/api/register` | Utenti registrati; gestione autenticazione |
| **Manufacturers** | `/api/manufacturers` | Produttori hardware (AMD, Intel, ASUS, …) |
| **Categories** | `/api/categories` | Categorie di componenti (CPU, GPU, RAM, …) con relative specifiche |
| **Components** | `/api/components` | Componenti hardware con specifiche tecniche e prezzo |
| **Builds** | `/api/builds` | Configurazioni PC create dagli utenti |
| **Build Components** | `/api/builds/{buildId}/components` | Componenti associati a una build, con verifica compatibilità |

---

### Associazioni tra le risorse (Schema E/R)


**Chiavi primarie:** `users.id`, `manufacturers.id`, `categories.id`, `components.id`, `builds.id`, `build_components.id`

**Associazioni principali:**
- `builds.user_id` → `users.id` (ogni build appartiene a un utente)
- `components.category_id` → `categories.id`
- `components.manufacturer_id` → `manufacturers.id`
- `build_components.build_id` → `builds.id`
- `build_components.component_id` → `components.id`
- `category_specs.category_id` → `categories.id`
- `component_specs.component_id` → `components.id`
- `compatibility_rules.category_id` → `categories.id`
- `compatibility_rules.target_category_id` → `categories.id`

---

## Operazioni CRUD e Documentazione API

*Vedi `http://localhost/docs` per la documentazione*

---

### 🔐 Autenticazione / Profile

#### `POST /api/register` — Registrazione utente

- **Autenticazione richiesta:** No
- **Body JSON:**

```json
{
  "username": "string",
  "password": "string"
}
```

- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `201 Created` | Registrazione avvenuta con successo | `"Register riuscito"` |
| `409 Conflict` | Username già in uso | `{"error": "Nome utente già utilizzato"}` |

---

#### `POST /api/login` — Login

- **Autenticazione richiesta:** No
- **Body JSON:**

```json
{
  "username": "string",
  "password": "string"
}
```

- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Login riuscito; imposta cookie `token` (JWT, durata 1h) | `{"description": "Login riuscito, cookie JWT impostato"}` |
| `400 Bad Request` | Credenziali errate o utente non trovato | `{"error": "..."}` |

---

#### `GET /api/profile` — Profilo utente corrente

- **Autenticazione richiesta:** Sì (cookie `token`)
- **Query string:** —
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Profilo restituito | `{"id": 1, "username": "Ale", "pfp_path": null, "created_at": "...", "is_owner": true}` |
| `401 Unauthorized` | Non autenticato | — |

---

### 🏭 Manufacturers

#### `GET /api/manufacturers` — Lista produttori

- **Autenticazione richiesta:** `AuthMiddleware` (utente loggato)
- **Query string:** —
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Lista produttori | Array di `{"name": "AMD", "url_name": "amd"}` |
| `401 Unauthorized` | Non autenticato | — |

---

#### `GET /api/manufacturers/{url_name}` — Dettaglio produttore

- **Autenticazione richiesta:** `OwnerAuthMiddleware`
- **Parametro path:** `url_name` (string) — es. `amd`
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Produttore trovato | `{"id": 11, "name": "AMD", "url_name": "amd"}` |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | Non owner | — |
| `404 Not Found` | Produttore non esistente | — |

---

#### `POST /api/manufacturers` — Crea produttore

- **Autenticazione richiesta:** `OwnerAuthMiddleware`
- **Body JSON:**

```json
{
  "name": "ASUS"
}
```

- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `201 Created` | Produttore creato | `{"description": "creata nuovo marca"}` |
| `400 Bad Request` | Errore nella richiesta | — |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | Non owner | — |

---

#### `DELETE /api/manufacturers/{manufacturerName}` — Elimina produttore

- **Autenticazione richiesta:** `OwnerAuthMiddleware`
- **Parametro path:** `manufacturerName` (string) — url_name del produttore
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `204 No Content` | Eliminato con successo | — |
| `400 Bad Request` | Produttore non esistente | `{"error": "Azienda non esistente"}` |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | Non owner | `{"error": "Non autorizzato"}` |

---

### 📂 Categories

#### `GET /api/categories` — Lista categorie

- **Autenticazione richiesta:** `AuthMiddleware`
- **Query string:** —
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Lista categorie | Array di `{"id": 1, "name": "Processori", "url_name": "processori"}` |
| `401 Unauthorized` | Non autenticato | — |

---

#### `GET /api/categories/{url_name}` — Dettaglio categoria

- **Autenticazione richiesta:** `OwnerAuthMiddleware`
- **Parametro path:** `url_name` (string) — es. `processori`
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Categoria trovata con le sue specifiche | `{"id": 24, "name": "Processori", "url_name": "processori", "specs": [{"key": "socket", "label": "Socket", "unit": ""}, {"key": "frequency", "label": "Frequenza", "unit": "GHz"}]}` |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | Non owner | — |
| `404 Not Found` | Categoria non esistente | — |

---

#### `POST /api/categories` — Crea categoria

- **Autenticazione richiesta:** `OwnerAuthMiddleware`
- **Body JSON:**

```json
{
  "name": "CPU",
  "specs": [
    {"key": "socket", "label": "Socket", "unit": ""},
    {"key": "frequency", "label": "Frequenza", "unit": "GHz"}
  ]
}
```

- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `201 Created` | Categoria creata con le sue specifiche | `{"description": "creata nuova categoria"}` |
| `400 Bad Request` | Errore nella richiesta | — |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | Non owner | — |

---

#### `DELETE /api/categories/{categoryName}` — Elimina categoria

- **Autenticazione richiesta:** `OwnerAuthMiddleware`
- **Parametro path:** `categoryName` (string) — url_name della categoria
- **Nota:** L'eliminazione è in cascade: vengono eliminate anche le specifiche associate e i componenti di quella categoria.
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `204 No Content` | Eliminata con successo | — |
| `400 Bad Request` | Categoria non esistente | — |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | Non owner | — |

---

### 🔩 Components

#### `GET /api/components` — Lista componenti

- **Autenticazione richiesta:** `AuthMiddleware`
- **Query string:**

| Parametro | Tipo | Default | Descrizione |
|---|---|---|---|
| `page` | integer | `1` | Numero di pagina (50 componenti per pagina) |

- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Lista componenti paginata | Array di `{"id": 1, "name": "Ryzen 5600G", "url_name": "ryzen-5600g", "price": 100, ...}` |
| `401 Unauthorized` | Non autenticato | — |

---

#### `GET /api/components/{url_name}` — Dettaglio componente

- **Autenticazione richiesta:** `OwnerAuthMiddleware`
- **Parametro path:** `url_name` (string) — es. `ryzen-5600g`
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Componente trovato | `{"id": 1, "name": "Ryzen 5600G", "url_name": "ryzen-5600g", "price": 100, "description": "...", ...}` |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | Non owner | — |
| `404 Not Found` | Componente non esistente | — |

---

#### `POST /api/components` — Crea componente

- **Autenticazione richiesta:** `OwnerAuthMiddleware`
- **Body JSON:**

```json
{
  "name": "Ryzen 5600G",
  "category": "processori",
  "manufacturer": "amd",
  "price": 100,
  "quantity": 1,
  "description": "Descrizione opzionale",
  "specs": {
    "socket": "AM4",
    "frequency": "3.9"
  }
}
```

- **Note:** `category` e `manufacturer` si specificano tramite il loro `url_name`. Il campo `specs` è un oggetto chiave→valore dove le chiavi corrispondono ai `spec_key` definiti nella categoria.
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `201 Created` | Componente creato | `{"id": 80, "message": "Componente creato"}` |
| `400 Bad Request` | Campi obbligatori mancanti o errore generico | — |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | Non owner | — |
| `409 Conflict` | Componente con lo stesso nome già esistente | — |

---

#### `DELETE /api/components/{url_name}` — Elimina componente

- **Autenticazione richiesta:** `OwnerAuthMiddleware`
- **Parametro path:** `url_name` (string)
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `204 No Content` | Eliminato con successo | — |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | Non owner | — |
| `404 Not Found` | Componente non esistente | — |

---

### 🖥️ Builds

#### `GET /api/builds` — Lista build dell'utente corrente

- **Autenticazione richiesta:** `AuthMiddleware`
- **Query string:** —
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Lista delle build dell'utente autenticato | Array di oggetti build |
| `401 Unauthorized` | Non autenticato | — |

Ogni oggetto build contiene: `id`, `user_id`, `name`, `description`, `status` (`draft`/`complete`/`published`), `is_public`, `total_price`, `created_at`, `updated_at`.

---

#### `GET /api/builds/{buildId}` — Dettaglio build

- **Autenticazione richiesta:** `AuthMiddleware`
- **Parametro path:** `buildId` (integer)
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Build trovata e appartenente all'utente | Oggetto build completo |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | La build non appartiene all'utente corrente | — |
| `404 Not Found` | Build non trovata | — |

---

#### `POST /api/builds` — Crea nuova build

- **Autenticazione richiesta:** `AuthMiddleware`
- **Body JSON:**

```json
{
  "name": "Il mio PC da gaming",
  "description": "Configurazione per gaming 4K"
}
```

- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `201 Created` | Build creata con successo | `{"description": "build creata con successo"}` |
| `401 Unauthorized` | Non autenticato | — |

---

#### `DELETE /api/builds/{buildId}` — Elimina build

- **Autenticazione richiesta:** `AuthMiddleware`
- **Parametro path:** `buildId` (integer)
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `204 No Content` | Build eliminata con successo | — |
| `401 Unauthorized` | Non autenticato | — |
| `403 Forbidden` | La build non appartiene all'utente corrente | — |
| `404 Not Found` | Build non trovata | — |

---

### 🔗 Build Components

#### `POST /api/builds/{buildId}/components` — Aggiungi componente a una build (con verifica compatibilità)

- **Autenticazione richiesta:** Sì (JWT verificato internamente)
- **Parametro path:** `buildId` (integer)
- **Body JSON:**

```json
{
  "component_id": 12
}
```

- **Note:** Prima di aggiungere il componente, il sistema verifica le regole di compatibilità (`compatibility_rules`) tra i componenti già presenti nella build e il nuovo. Se ci sono conflitti (es. socket diversi), restituisce `409` con l'elenco degli errori invece di procedere all'inserimento.
- **Risposte:**

| Codice | Situazione | Body |
|---|---|---|
| `200 OK` | Componente compatibile, aggiunto con successo | — |
| `400 Bad Request` | `component_id` mancante o build non trovata | — |
| `401 Unauthorized` | Non autenticato | — |
| `409 Conflict` | Conflitti di compatibilità rilevati | `{"errors": ["Componente non compatibile: AM5 != AM4"]}` |

---

## Database

### Schema logico delle tabelle

#### `users`
| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `username` | VARCHAR(50) | UNIQUE, NOT NULL |
| `password_hash` | TEXT | NOT NULL (bcrypt) |
| `pfp_path` | TEXT | NULL |
| `created_at` | TIMESTAMP | DEFAULT NOW() |
| `is_owner` | TINYINT(1) | DEFAULT 0 — flag amministratore |

#### `manufacturers`
| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `name` | VARCHAR(100) | NOT NULL |
| `url_name` | VARCHAR(100) | NOT NULL (slug, es. `amd`) |

#### `categories`
| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `name` | VARCHAR(100) | NOT NULL |
| `url_name` | VARCHAR(100) | NOT NULL (slug, es. `processori`) |

#### `category_specs`
| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `category_id` | INT | FK → `categories.id` (CASCADE DELETE) |
| `spec_key` | VARCHAR(100) | Chiave della specifica (es. `socket`) |
| `spec_label` | VARCHAR(100) | Etichetta leggibile (es. `Socket`) |
| `unit` | VARCHAR(20) | Unità di misura (es. `GHz`, vuoto se assente) |

#### `components`
| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `category_id` | INT | FK → `categories.id` (CASCADE DELETE) |
| `manufacturer_id` | INT | FK → `manufacturers.id` (CASCADE DELETE) |
| `name` | VARCHAR(200) | UNIQUE, NOT NULL |
| `url_name` | VARCHAR(200) | UNIQUE, NOT NULL (slug) |
| `image_path` | VARCHAR(200) | NULL |
| `description` | VARCHAR(400) | NULL |
| `created_at` | TIMESTAMP | DEFAULT NOW() |
| `quantity` | INT | DEFAULT 1 |
| `price` | INT | NULL (in centesimi o unità intera) |

#### `component_specs`
| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `component_id` | INT | FK → `components.id` (CASCADE DELETE) |
| `spec_key` | VARCHAR(100) | es. `socket`, `frequency` |
| `spec_value` | TEXT | Valore della specifica |
| `unit` | VARCHAR(20) | Unità di misura |

#### `builds`
| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `user_id` | INT | FK → `users.id` (CASCADE DELETE) |
| `name` | VARCHAR(100) | NOT NULL |
| `description` | TEXT | NOT NULL |
| `status` | ENUM(`draft`,`complete`,`published`) | DEFAULT `draft` |
| `is_public` | TINYINT(1) | DEFAULT 0 |
| `total_price` | DECIMAL(10,2) | DEFAULT 0.00 |
| `created_at` | TIMESTAMP | DEFAULT NOW() |
| `updated_at` | TIMESTAMP | Aggiornato automaticamente |

#### `build_components`
| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `build_id` | INT | FK → `builds.id` |
| `component_id` | INT | FK → `components.id` (CASCADE DELETE) |
| `quantity` | INT | DEFAULT 1 |
| `price_at_add` | DECIMAL(10,2) | Prezzo al momento dell'aggiunta, NULL se non registrato |

#### `compatibility_rules`
| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PK |
| `category_id` | INT | FK → `categories.id` — categoria del componente da aggiungere |
| `target_category_id` | INT | FK → `categories.id` — categoria del componente già in build |
| `spec_key` | VARCHAR(100) | Specifica del componente da aggiungere da confrontare |
| `target_spec_key` | VARCHAR(100) | Specifica del componente in build da confrontare |
| `operator` | ENUM(`=`,`<`,`>`) | NULL — operatore di confronto (NULL = confronto di uguaglianza tramite `required_value`) |
| `required_value` | VARCHAR(100) | NULL — valore atteso per l'operatore |

---

## Configurazione

Il file `config/config.yaml` contiene i dati di configurazione del web service:

```yaml
app:
  debug: false
  allowed_hosts:
    - localhost
    - 127.0.0.1
  docs:
    /docs/swagger.html

directories:
  controllers: /app/routes
  middlewares: /app/middlewares
  static: /static
```

---


## Organizzazione dei lavori

TODO
