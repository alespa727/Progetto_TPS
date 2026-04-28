Sistema di gestione delle autorizzazioni sulle richieste

## Livelli di autorizzazione

Il web service implementa due livelli di accesso, verificati tramite cookie JWT (`token`):

| Middleware | Requisito | Risposta in caso di errore |
|---|---|---|
| *(nessuno)* | Accesso libero | — |
| `AuthMiddleware` | Utente autenticato (login effettuato) | `401 Unauthorized` |
| `OwnerAuthMiddleware` | Utente autenticato **e** con flag `is_owner = true` | `401` se non loggato, `403 Forbidden` se loggato ma non owner |