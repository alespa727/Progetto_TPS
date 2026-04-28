# Introduzione al progetto

Il nostro progetto consiste in un applicazione flutter e web-service per la progettazione di computer fissi personalizzati. L'applicazione permette di selezionare progressivamente diversi componenti come il processore, scheda madre, ram, alimentatore e storage. Tutto attraverso un'interfaccia giudata e adattando automaticamente le opzioni compatibili in base alle scelte precedenti.


- Consultare un catalogo di **componenti hardware** (CPU, GPU, RAM, ecc.) organizzati per categoria e produttore.
- **Creare e gestire build** (configurazioni PC) personalizzate, aggiungendo componenti con verifica automatica della compatibilità.
- Gestire **categorie** di componenti con le relative specifiche tecniche (es. socket, frequenza).
- Gestire i **produttori** dei componenti.
- Autenticarsi tramite **JWT** (cookie `token`) con due livelli di accesso: utente normale e owner (amministratore).


## Caratteristiche distintive

Elementi technici innovativi del nostro progetto.

<div class="grid cards" markdown>

- :simple-framework: __Framework__ – Framework programmato da zero, secondo standard moderni e interamente assestante e riutilizzabile.
- :octicons-cache-16: __Caching__ – Alta Scalabilità grazie alle prestazioni avanzate garantite dal sistema di chaching integrato.
- :material-page-layout-header: __Endpoints__ – flessibilità e semplicità d'uso per programmare endpoint custom in base alle proprie esigenze.
- :simple-openapiinitiative: __OpenAPI__ – Ottima compatibilità grazie alle api conformi alle specificazione openAPI
- :simple-swagger: __Swagger UI__ – Integrazione di Swagger Ui che presenta la api in modo interattivo ed efficace per rendere il development del frontend ancora più diretto.
- :octicons-lock-16: __Authentication__ Sistema di gestione delle autorizzazioni sulle richieste
</div>

---

## Operazioni CRUD e Documentazione API

*Vedi `http://localhost/docs` per la documentazione*

---

## Struttura delle directory

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