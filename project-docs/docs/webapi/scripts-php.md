# Scripts (Metodi) coinvolti nelle richieste
Il framework usa il paradigma **Object-oriented**, per cui si fa riferimento a file php che fungono da classi distinte.

## Framework flow
1. Creazione oggetto Request
2. Gestione il CORS
3. Determinazione rotta corretta tramite cache
4. Istanziazmento del controller appropriato
4. Esecuzione delll'azione richiesta
5. Restituzione della risposta al client

---

## Script PHP di gestione (Entry Point)

Tutti gli indirizzi vengono riscritti da Apache (mod_rewrite) verso un unico entry point:

| Script | Quando viene richiamato |
|---|---|
| `index.php` | Viene richiamato per **ogni richiesta HTTP** al web service. Carica la configurazione da `config/config.yaml`, inizializza il `Router` e `FileHandler`, e delega la gestione della richiesta al `Router`. |

---

## Descrizione Generale Router
Il `Router` (`framework/Router.php`) riceve la richiesta da `index.php`, individua il controller corretto in base a metodo HTTP e percorso URI, applica i middleware di autorizzazione e delega l'esecuzione al controller appropriato.

---

## Metodi per la gestione di richieste

Tutte le classi dei metodi citati sono in `/framework`.

| Metodo | Funzione |
|---|---|
| `Router::init()` | 1. Si salva configurazione delle directory da `Config`.<br>2. Chiama `RouteBuilder::build` in caso di modifiche alle route. |
| `RouteBuilder::build()` | Scansiona ricorsivamente una directory, costruisce un albero di route indicizzato e lo salva in cache come array associativo in `framework/cache/routes.php`. |
| `Router::handle()` | Gestisce una richiesta HTTP cercando una corrispondenza tra l'URI e le route registrate.<br>1. Istanzia oggetto `Request`<br>2. Gestisce CORS<br>3. Match route<br>4. Chiama `runMiddlewares()`<br>5. Invoca `Controller->manageRequest`.<br>6. Invia Risposta|
| `runMiddlewares()` | Chiama una catena di `Middleware` per validare la richiesta. | 
| `Controller->manageRequest` | Istanzia il controller (se necessario) e invoca il suo handler `__invoke`. |
