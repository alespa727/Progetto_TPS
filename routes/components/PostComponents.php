<?php

use Core\Exceptions\BadRequest;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use Core\ApiDoc;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

#[Route(Method::Post, ["api", "components"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
class PostComponents extends Controller
{

    function validateRequest(Request $request, Params $params): bool
    {
        $category = $request->getBody("category");
        $manufacturer = $request->getBody("manufacturer");
        $name = $request->getBody("name");

        if (!isset($category) || !isset($manufacturer) || !isset($name)) {
            return false;
        }

        return true;
    }


    function manageRequest(Request $request, Params $params): Response
    {
        $category = $request->getBody("category");
        $manufacturer = $request->getBody("manufacturer");
        $name = $request->getBody("name");
        $nome_url = str_replace(" ", "-", strtolower($name));
        $description = $request->getBody("description") ?? "";
        $quantity = $request->getBody("quantity") ?? 1;

        /**
         * @var array $specs
         */
        $specs = $request->getBody("specs");

        $db = \DatabaseUtil\Database::getDatabase();

        $pr = $db->prepare("SELECT id FROM categories WHERE url_name=?");
        $pr->execute([$category]);

        $category_id = $pr->fetch(PDO::FETCH_ASSOC)["id"];

        $pr = $db->prepare("SELECT id FROM manufacturers WHERE url_name=?");
        $pr->execute([$manufacturer]);

        $manufacturer_id = $pr->fetch(PDO::FETCH_ASSOC)["id"];

        $pr = $db->prepare("INSERT INTO components (category_id, manufacturer_id, name, url_name, description, quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $success = $pr->execute([$category_id, $manufacturer_id, $name, $nome_url, $description, $quantity]);
        $componentId = $db->lastInsertId();

        $pr = $db->prepare("INSERT INTO component_specs (component_id, spec_key, spec_value, unit) VALUES (:component_id, :spec_key, :spec_value, :unit)");

        $prUnits = $db->prepare("SELECT spec_key, unit FROM category_specs WHERE category_id = :category_id");
        $prUnits->execute(["category_id" => $category_id]);
        $units = array_column($prUnits->fetchAll(PDO::FETCH_ASSOC), 'unit', 'spec_key');

        if ($success) {

            foreach ($specs as $key => $spec) {
                $pr->execute([
                    "component_id" => $componentId,
                    "spec_key" => $key,
                    "spec_value" => $spec,
                    "unit" => $units[$key] ?? '',
                ]);
            }
            $res = Response::new()->created()->body(["id" => $componentId, "message" => "Componente creato"]);
            return $res;
        } else {
            throw new BadRequest("Componente non esistente");
        }
        return $res;

    }
}
