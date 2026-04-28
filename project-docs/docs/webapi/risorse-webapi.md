
## Lista risorse

| Risorsa | URI base | Descrizione |
|---|---|---|
| **Users** | `/api/profile`, `/api/login`, `/api/register` | Utenti registrati; gestione autenticazione |
| **Manufacturers** | `/api/manufacturers` | Produttori hardware (AMD, Intel, ASUS, …) |
| **Categories** | `/api/categories` | Categorie di componenti (CPU, GPU, RAM, …) con relative specifiche |
| **Components** | `/api/components` | Componenti hardware con specifiche tecniche e prezzo |
| **Builds** | `/api/builds` | Configurazioni PC create dagli utenti |
| **Build Components** | `/api/builds/{buildId}/components` | Componenti associati a una build, con verifica compatibilità |
| **Rules** | `/api/rules` | Regole di compatibilità tra componenti |

!!! info "Operazioni CRUD e Documentazione API"
    *Avvia web service e vedi `http://localhost/docs` per la documentazione api*

---

## Relazioni risorse

### ER:
``` mermaid
erDiagram
    Build_Components ||--o{ Components : contains
```
### Chiave:
`component_id`
