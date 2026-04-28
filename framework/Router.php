<?php
namespace Core;

use Core\RouteBuilder;

/**
 * Router principale dell'applicazione.
 *
 * Si occupa di:
 * - Ricevere la richiesta HTTP
 * - Determinare la rotta corretta
 * - Istanziare il controller appropriato
 * - Eseguire l'azione richiesta
 * - Restituire la risposta al client
 */
class Router
{

    private static $routesPath = "";
    private static $middlewarePath = "";
    private static $debug = false;

    /**
     * Gestisce il controllo CORS sulla richiesta.
     *
     * Verifica che l'hostname della richiesta sia tra quelli consentiti.
     * Se non è autorizzato, invia una risposta 401 e interrompe il flusso.
     *
     * @param string[] $allowedHosts Lista degli hostname consentiti
     * @return void
     */
    static function handleCors(array $allowedHosts)
    {
        if (!cors($allowedHosts)) {
            Router::sendResponse(
                Response::new()
                    ->unauthorized()
                    ->body(["error" => "Richiesta da hostname non valido"]),
                ContentTypes::Json
            );
        }
    }

    /**
     * Inizializza il router del framework.
     *
     * Carica le configurazioni principali include le funzioni di supporto 
     * e rigenera la cache delle rotte se sono state modificate.
     *
     * @return void
     */
    static function init(): void
    {
        Router::$routesPath = Config::path("directories.controllers");
        Router::$middlewarePath = Config::path("directories.middlewares");
        Router::$debug = Config::get("app.debug");


        if (Router::$debug) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }

        include_once "functions.php";
        if (routesHaveChanged(Router::$routesPath)) {
            RouteBuilder::build(self::$routesPath);
        }

    }

    /**
     * Capibile dal nome
     * @return array|null
     */
    public static function getRoutes(): array
    {
        return RouteBuilder::getAllRoutes(self::$routesPath);
    }

    /**
     * Trova la rotta che corrisponde alla richiesta HTTP.
     *
     * Analizza i segmenti dell'URL e il metodo HTTP per individuare
     * la rotta corrispondente nella struttura delle rotte. Supporta
     * parametri dinamici e validazione del tipo (int, string, ecc.).
     *
     * Se viene trovata una corrispondenza:
     * - imposta i parametri nella richiesta
     * - carica dinamicamente il controller associato
     *
     * In caso di errore:
     * - invia 404 se la rotta non esiste
     * - invia 405 se il metodo non è consentito
     *
     * @param Request $request Richiesta HTTP (viene modificata con i parametri estratti)
     * @param array $routes Struttura ad albero delle rotte
     * @return array|null Dati della rotta trovata oppure null se non esiste
     */
    static function findMatch(Request &$request, array $routes): array|null
    {

        // VEDERE COME SONO GENERATE LE ROUTE IN ROUTEBUILDER.PHP PER CAPIRE QUESTO

        $route = null;
        $array = $routes;
        $segments = $request->getSegments();
        $request_method = $request->getMethod();
        $params = [];

        $i = 0;

        if (empty($segments)) {
            if (isset($array["_" . $request_method])) {
                $route = $array["_" . $request_method];
            }
        }

        foreach ($segments as $key => $segment) {
            $isLast = $i === count($segments) - 1;

            if (!$isLast) {

                if (array_key_exists($segment, $array)) {
                    
                    $array = &$array[$segment];

                } else {
                    
                    if (array_key_exists("_param", $array)) {
                        $param = explode(":", $array["_param"]);
                    }

                    $paramName = null;
                    if (isset($param))
                        $paramName = $param[0];

                    if (array_key_exists("_type", $array)) {
                        $type = $array["_type"];
                    }

                    if (!$paramName)
                        continue;
                    $name = substr($paramName, 1, -1);

                    $isValidType = false;

                    $array = &$array[$paramName];

                    $params[$name] = $segment;

                }
            } else {
                if (!is_array($array)) {
                    $res = Response::new()
                        ->status(HttpResponseCodes::NOT_FOUND)
                        ->body(["description" => "route non trovata"]);
                    Router::sendResponse($res, ContentTypes::Json);
                }

                if (array_key_exists($segment, $array)) {
                    if (array_key_exists("_" . $request_method, $array[$segment]))
                        $route = $array[$segment]["_" . $request_method];
                    else if (count($array[$segment]["methods"]) > 0) {
                        $res = Response::new()
                            ->status(HttpResponseCodes::METHOD_NOT_ALLOWED)
                            ->body(["description" => "metodo non valido"]);
                        Router::sendResponse($res, ContentTypes::Json);
                    }

                } else if (array_key_exists("_param", $array)) {

                    $param = explode(":", $array["_param"]);

                    $paramName = $param[0];

                    $type = $array["_type"];

                    $name = substr($paramName, 1, -1);

                    $isValidType = false;

                    switch ($type) {
                        case 'int':
                            $isValidType = is_numeric($segment);
                            break;
                        case 'string':
                            $isValidType = !is_numeric($segment);
                            break;
                        default:
                            $isValidType = false;
                            break;
                    }

                    if ($isValidType) {
                        $params[$name] = $segment;
                    }

                    if (array_key_exists("_" . $request_method, $array[$paramName])) {
                        $route = $array[$paramName]["_" . $request_method];
                    }

                }


            }
            $i++;
        }

        if ($route) {
            $request->setParams($params);
            $className = $route["controller"];

            $path = $route["controller_path"];

            if (file_exists($path) && !class_exists($className, false)) {
                require $path;
            }

        }

        return $route;
    }


   /**
     * Gestisce una richiesta HTTP cercando una corrispondenza tra l'URI e le route registrate.
     *
     * Flusso: creazione Request → CORS → match route → esecuzione middleware → invocazione handler.
     * Al termine aggiunge l'header `X-time` con il tempo di elaborazione in ms.
     *
     * In caso di route non trovata risponde con 404. In caso di eccezione risponde con il codice
     * HTTP dell'eccezione (default 500), includendo dettagli aggiuntivi se {@see Router::$debug} è attivo.
     *
     * @param float $start Timestamp di inizio richiesta ottenuto con {@see microtime(true)}.
     * @return void        La risposta viene inviata direttamente al client; le eccezioni sono gestite internamente.
     *
     */
    static function handle($start)
    {
        $request = new Request();
        Router::handleCors(Config::get('app.allowed_hosts', []));

        $routes = (require "cache/routes.php");


        $route = Router::findMatch($request, $routes);
        if (!$route) {
            // Altrimenti 404
            Router::sendResponse(
                Response::new()
                    ->notFound()
                    ->body(['error' => 'Route non trovata']),
                ContentTypes::Json
            );
        }

        $requiredFiles = [];
        $routeInstance = Route::fromArray($route);
        $requested_middleware = $routeInstance->getMiddlewares();

        runMiddleware(
            $request,
            $requested_middleware,
            function () use ($routeInstance, $request, $start) {
                $res = null;

                try {
                    $res = $routeInstance->manageRequest($request, new Params($request->getParams()));
                    if (!$res || $res->body === null || $routeInstance->getContentType() === null || $res->responseCode === null) {
                        throw new \Core\Exceptions\InternalServerError("Ricontrolla il codice mona");
                    }
                } catch (\Throwable $th) {
                    $status = $th->getCode();

                    if (!is_int($status) || $status < 100 || $status > 599) {
                        $status = 500;
                    }
                    if (Router::$debug) {
                        $res = Response::new()
                            ->status($status)
                            ->body([
                                "error" => $th->getMessage()
                            ]);

                        Router::sendResponse($res, ContentTypes::Json);
                    } else {
                        $res = Response::new()
                            ->status($status ?? 500)
                            ->body([
                                "error" => $th->getMessage() ?? "Internal Server Error"
                            ]);

                        Router::sendResponse($res, ContentTypes::Json);

                    }

                } finally {

                    $end = microtime(true);
                    $elapsed = $end - $start;
                    $res->header("X-time: " . (floor($elapsed * 1000 * 100) / 100) . " ms");

                    Router::sendResponse($res, $routeInstance->getContentType());

                }
            }
        );

    }

    /**
     * Invia gli header HTTP della risposta, inclusi codice di stato e Content-Type.
     *
     * @param Response $response Oggetto risposta contenente codice HTTP e headers personalizzati.
     * @param string   $type     Header Content-Type da inviare (es. {@see ContentTypes::Json}).
     * @return void
     */
    static function sendHeaders(Response $response, string $type)
    {
        http_response_code($response->responseCode);
        foreach ($response->headers as $header) {
            header($header);
        }
        header($type);
    }

    /**
     * Invia la risposta HTTP completa al client (header + body) e termina l'esecuzione.
     *
     * Il formato del body dipende dal Content-Type
     * 
     * @param Response $response    Oggetto risposta con codice HTTP, headers e body.
     * @param string   $contentType Content-Type che determina il formato di output.
     * @return never                Termina sempre con {@see die}.
     */
    static function sendResponse(Response $response, string $contentType)
    {
        Router::sendHeaders($response, $contentType);

        if ($contentType === ContentTypes::Json) {
            echo json_encode($response->body);
        } else if ($contentType === ContentTypes::DownloadFile) {
            FileHandler::sendFileDownloadResponse($response->file["path"], $response->file["filename"]);
        } else if ($contentType === ContentTypes::InlineFile) {
            FileHandler::returnInlineFile($response->file["path"]);
        } else {
            echo $response->body;
        }
        die;
    }
}


