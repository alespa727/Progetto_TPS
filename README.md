# TPSIT – Web Service Documentation

**Materia:** TPSIT (Tecnologie e Progettazione di Sistemi Informatici e di Telecomunicazioni)

**Membri del gruppo:** Spartano Alessio, Warnakulasuriya Ashan, Lorenzo Santangelo, Zanette Andrea

---

## Indice

1. [Introduzione](#1-introduzione)
   - 1.0 [Setup](#10-setup)
   - 1.1 [Obiettivo](#11-obiettivo)
   - 1.2 [Caratteristiche](#12-caratteristiche)
   - 1.3 [Struttura Directory](#13-struttura-directory)
   - 1.4 [Configurazione](#14-configurazione)
2. [Web API](#2-web-api)
   - 2.1 [Informazioni Generali](#21-informazioni-generali)
   - 2.2 [Lista Risorse](#22-lista-risorse)
   - 2.3 [Relazioni tra Risorse](#23-relazioni-tra-risorse)
3. [Autenticazione](#3-autenticazione)
   - 3.1 [Livelli di Autorizzazione](#31-livelli-di-autorizzazione)
4. [Scripts PHP](#4-scripts-php)
   - 4.1 [Flusso del Framework](#41-flusso-del-framework)
   - 4.2 [Entry Point](#42-entry-point)
   - 4.3 [Descrizione Generale Router](#43-descrizione-generale-router)
   - 4.4 [Metodi per la Gestione di Richieste](#44-metodi-per-la-gestione-di-richieste)
5. [Database](#5-database)
   - 5.1 [Schema Logico](#51-schema-logico)
   - 5.2 [Schema ER Semplice](#52-schema-er-semplice)
   - 5.3 [Schema ER Completo](#53-schema-er-completo)

---

## 1. Introduzione

### 1.0 Setup

> 💡 Si consiglia la lettura della documentazione direttamente sul nostro web service già hostato su [http://sketchpc.hopto.org/mkdocs](http://sketchpc.hopto.org/mkdocs)

**Requisiti di sistema:**

- PHP = 8.12.2
- Composer
- MariaDB

**Installazione delle dipendenze** (JWT, Swagger, Var Exporter):

```bash
composer install
composer dump-autoload
```

---

### 1.1 Obiettivo

Questo progetto di gruppo ha lo scopo di integrare e applicare le conoscenze e le competenze acquisite nelle discipline di **Informatica** (per database) e **TPSIT**.

Si lavora in gruppo per progettare, sviluppare e documentare un'applicazione software che risponda a specifiche esigenze, tra le quali quella di realizzare un **web service in PHP** con framework autosufficiente progettato *from scratch*.

L'obiettivo che il team si è dato per questo progetto è quello di sviluppare un **servizio per la progettazione di computer fissi personalizzati**. Il servizio permette di:

- Selezionare progressivamente diversi componenti (processore, scheda madre, RAM, alimentatore, storage)
- Visualizzare le componenti compatibili con le scelte precedenti
- Salvare le configurazioni nell'account personale dello user

---

### 1.2 Caratteristiche

Il progetto ha molte caratteristiche ma, tra le più importanti, ci sono:

- Consultare un catalogo di componenti hardware (CPU, GPU, RAM, ecc.) organizzati per categoria e produttore.
- Creare e gestire build (configurazioni PC) personalizzate, con verifica automatica della compatibilità.
- Gestire categorie di componenti con le relative specifiche tecniche (es. socket, frequenza).
- Gestire i produttori dei componenti.
- Autenticarsi tramite JWT (cookie `token`) con due livelli di accesso: utente normale e owner (amministratore).

| Caratteristica | Descrizione |
|---|---|
| **Framework** | Programmato da zero, secondo standard moderni, interamente autonomo e riutilizzabile. |
| **Caching** | Alta scalabilità grazie alle prestazioni avanzate garantite dal sistema di caching integrato. |
| **Endpoints** | Flessibilità e semplicità per programmare endpoint custom in base alle proprie esigenze. |
| **OpenAPI** | Ottima compatibilità grazie alle API conformi alle specifiche OpenAPI. |
| **Swagger UI** | Integrazione di Swagger UI che presenta le API in modo interattivo ed efficace. |
| **Authentication** | Sistema di gestione delle autorizzazioni sulle richieste tramite JWT. |

---

### 1.3 Struttura Directory

```
htdocs
├── index.php                   ← entry point unico (eseguito dal web server)
├── .htaccess                   ← rewrite engine: tutte le richieste → index
├── config/
│   └── config.yaml             ← configurazione (debug, hosts, path)
├── app/
│   ├── authorization/          ← gestione JWT e auth
│   │   └── Authorization.php
│   ├── middlewares/            ← middleware di autenticazione
│   │   ├── AuthMiddleware.php
│   │   └── OwnerAuthMiddleware.php
│   └── routes/                 ← controller per ogni risorsa
│       ├── builds/
│       ├── categories/
│       ├── components/
│       ├── manufacturers/
│       └── profile/
├── framework/                  ← codice generale (Router, Request, Response…)
└── db.sql                      ← file SQL per la creazione del database
```

---

### 1.4 Configurazione

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

## 2. Web API

### 2.1 Informazioni Generali

- **URL del web service:** `http://localhost`
- Avvia il web service e visita `http://localhost/documentazione` per la documentazione API.
- Oppure usa il web server hostato online: [http://sketchpc.hopto.org/documentazione](http://sketchpc.hopto.org/documentazione)

---

### 2.2 Lista Risorse

| Risorsa | URI base | Descrizione |
|---|---|---|
| **Users** | `/api/profile`, `/api/login`, `/api/register` | Utenti registrati; gestione autenticazione |
| **Manufacturers** | `/api/manufacturers` | Produttori hardware (AMD, Intel, ASUS, …) |
| **Categories** | `/api/categories` | Categorie di componenti (CPU, GPU, RAM, …) con relative specifiche |
| **Components** | `/api/components` | Componenti hardware con specifiche tecniche e prezzo |
| **Builds** | `/api/builds` | Configurazioni PC create dagli utenti |
| **Build Components** | `/api/builds/{buildId}/components` | Componenti associati a una build, con verifica compatibilità |
| **Rules** | `/api/rules` | Regole di compatibilità tra componenti |

---

### 2.3 Relazioni tra Risorse

| Entità | Relazione | Chiave |
|---|---|---|
| Build_Components | contiene → | `component_id` |
| Components | ← contenuto in | `component_id` |

---

## 3. Autenticazione

Il web service implementa due livelli di accesso, verificati tramite **cookie JWT** (`token`).

### 3.1 Livelli di Autorizzazione

| Middleware | Requisito | Risposta in caso di errore |
|---|---|---|
| *(nessuno)* | Accesso libero | — |
| `AuthMiddleware` | Utente autenticato (login effettuato) | `401 Unauthorized` |
| `OwnerAuthMiddleware` | Utente autenticato e con flag `is_owner = true` | `401` se non loggato, `403 Forbidden` se loggato ma non owner |

---

## 4. Scripts PHP

Il framework usa il paradigma **Object-oriented**. Si fa riferimento a file PHP che fungono da classi distinte.

### 4.1 Flusso del Framework

1. Creazione oggetto `Request`
2. Gestione del CORS
3. Determinazione rotta corretta tramite cache
4. Istanziamento del controller appropriato
5. Esecuzione dell'azione richiesta
6. Restituzione della risposta al client

---

### 4.2 Entry Point

| Script | Quando viene richiamato |
|---|---|
| `index.php` | Viene richiamato per ogni richiesta HTTP al web service. Carica la configurazione da `config/config.yaml`, inizializza il Router e FileHandler, e delega la gestione della richiesta al Router. |

---

### 4.3 Descrizione Generale Router

Il **Router** (`framework/Router.php`) riceve la richiesta da `index.php`, individua il controller corretto in base a metodo HTTP e percorso URI, applica i middleware di autorizzazione e delega l'esecuzione al controller appropriato.

---

### 4.4 Metodi per la Gestione di Richieste

> Tutte le classi dei metodi citati si trovano in `/framework`.

| Metodo | Funzione |
|---|---|
| `Router::init()` | Salva la configurazione delle directory da Config. Chiama `RouteBuilder::build` in caso di modifiche alle route. |
| `RouteBuilder::build()` | Scansiona ricorsivamente una directory, costruisce un albero di route indicizzato e lo salva in cache come array associativo in `framework/cache/routes.php`. |
| `Router::handle()` | Gestisce una richiesta HTTP cercando corrispondenza tra URI e route registrate. Istanzia `Request`, gestisce CORS, effettua match route, chiama `runMiddlewares()`, invoca `Controller->manageRequest` e invia la risposta. |
| `runMiddlewares()` | Chiama una catena di Middleware per validare la richiesta. |
| `Controller->manageRequest()` | Istanzia il controller (se necessario) e invoca il suo handler `__invoke`. |

---


### 5.1 Database

**USERS**

| Tipo | Campo |
|---|---|
| int | `id` PK |
| string | `username` |
| string | `password_hash` |
| datetime | `created_at` |
| bool | `is_owner` |
| string | `pfp_hash` |

**BUILDS**

| Tipo | Campo |
|---|---|
| int | `id` PK |
| int | `user_id` FK |
| string | `name` |
| string | `description` |
| string | `status` |
| bool | `is_public` |
| decimal | `total_price` |
| datetime | `created_at` |
| datetime | `updated_at` |

**BUILD_COMPONENTS**

| Tipo | Campo |
|---|---|
| int | `id` PK |
| int | `build_id` FK |
| int | `component_id` FK |
| int | `quantity` |

**MANUFACTURERS**

| Tipo | Campo |
|---|---|
| int | `id` PK |
| string | `name` |
| string | `url_name` |

**COMPONENTS**

| Tipo | Campo |
|---|---|
| int | `id` PK |
| int | `category_id` FK |
| int | `manufacturer_id` FK |
| string | `name` |
| string | `url_name` |
| string | `description` |
| datetime | `created_at` |
| int | `quantity` |
| int | `price` |
| string | `image_hash` |

**COMPONENT_SPECS**

| Tipo | Campo |
|---|---|
| int | `id` PK |
| int | `component_id` FK |
| string | `spec_key` |
| string | `spec_value` |
| string | `unit` |

**CATEGORIES**

| Tipo | Campo |
|---|---|
| int | `id` PK |
| string | `name` |
| string | `url_name` |
| int | `max_per_build` |

**CATEGORY_SPECS**

| Tipo | Campo |
|---|---|
| int | `id` PK |
| int | `category_id` FK |
| string | `spec_key` |
| string | `spec_label` |
| string | `unit` |

**COMPATIBILITY_RULES**

| Tipo | Campo |
|---|---|
| int | `id` PK |
| int | `category_id` FK |
| int | `target_category_id` FK |
| string | `spec_key` |
| string | `target_spec_key` |
| string | `operator` |
| string | `required_value` |
