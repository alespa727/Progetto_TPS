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
