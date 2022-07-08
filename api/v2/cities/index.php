<?php
include_once "../../../loader.php";
use \App\Services\CityService;
use \App\Utilities\Response;


$resquest_method = $_SERVER['REQUEST_METHOD'];
$request_body = json_decode(file_get_contents('php://input'),true);
$city_service = new CityService();

switch ($resquest_method){
    case 'GET':

        $province_id = $_GET['province_id'] ?? null;
        $request_data = [
            'province_id' => $province_id,
        ];
        $response = $city_service->getCities($request_data);
        if(empty($response))
            Response::respondAndDie($response,Response::HTTP_NOT_FOUND);
        Response::respondAndDie($response,Response::HTTP_OK);
        
    case 'POST':

        if(!isValidCity($request_body))
                Response::respondAndDie(['Invalid City Data'],Response::HTTP_NOT_ACCEPTABLE);
            $response = $city_service->createCity($request_body);
            Response::respondAndDie($response,Response::HTTP_CREATED);

    case 'PUT':

        [$city_id,$city_name] =[$request_body['city_id'],$request_body['name']];
        if(!is_numeric($city_id) or empty($city_name))
            Response::respondAndDie(['Invalid City Data'],Response::HTTP_NOT_ACCEPTABLE);
        $result=$city_service->updateCityName($city_id,$city_name);
        Response::respondAndDie($result,Response::HTTP_OK);

    case 'DELETE':

        $city_id=$_GET['city_id'] ?? null;
        if(!is_numeric($city_id) or is_null($city_id))
            Response::respondAndDie(['Invalid City id'],Response::HTTP_NOT_ACCEPTABLE);
        $result=$city_service->deleteCity($city_id);
        Response::respondAndDie($result,Response::HTTP_OK);
        
    default:
        Response::respondAndDie(['Invalid request Method'],Response::HTTP_METHOD_NOT_ALLOWED);
}