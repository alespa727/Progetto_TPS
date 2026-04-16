# Documentazione Progetto Web API

## Descrizione sintetica della realtà di riferimento
*Il progetto consiste in un sistema per la configurazione personalizzata di computer . L'applicazione permette di consultare un catalogo di componenti hardware (CPU, GPU, RAM, etc.), verificare la compatibilità di base e gestire liste di configurazioni (Build) create dagli utenti.*

---

## Web API

### URL del Web Service
`http://localhost/`

### Script PHP di gestione (Entry Points)
Questi sono gli script richiamati direttamente dal web server:

| Script           | Caso d'uso / Quando viene richiamato                                                                                                                                                                      |
|:-----------------|:----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `index.php`      | Avvia e inizializza la configurazione della classe `Router` dal file `Router.php`                                                                                                                         |
| `Router.php`     | Si occupa di: <br/>- Ricevere la richiesta HTTP<br/>- Determinare la rotta corretta<br/>- Istanziare il controller appropriato<br/> - Eseguire l'azione richiesta<br/> - Restituire la risposta al client |
| `Controller.php` | Classe base astratta, utilizzata da ogni endpoint per definire la risposta. Prima valida la richiesta (validateRequest) e poi la esegue (manageRequest).                                                  |



### Risorse gestite



### Associazioni tra le risorse (Schema E/R)


##  Operazioni CRUD e Documentazione API

Per visualizzare la documentazione api visitare: `http://localhost/docs/`

---

## Database


### Schema Logico delle Tabelle
