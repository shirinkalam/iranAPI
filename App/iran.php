<?php
use \Firebase\JWT\JWT;

try {
    $pdo = new PDO("mysql:dbname=iran2;host=localhost", 'root', '');
    $pdo->exec("set names utf8;");
    // echo "Connection OK!";
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

#==============  Simple Validators  ================
function isValidCity($data){
    if(empty($data['province_id']) or !is_numeric($data['province_id']))
        return false;
    return empty($data['name']) ? false : true;
}
function isValidProvince($data){
    # its better to validate data in database
    $province_id = intval($data['province_id'] ?? 0);
    if($province_id < 1 OR $province_id > 31)
        return false;
    return true;
}


#================  Read Operations  =================
function getCities($data = null){
    global $pdo;
    $province_id = $data['province_id'] ?? null;
    $fields = $data['fields'] ?? '*';
    $orderby = $data['orderby'] ?? null;
    $page = $data['page'] ?? null;
    $pagesize = $data['pagesize'] ?? null;
    $orderByStr = '';
    if(!is_null($orderby))
        $orderByStr = " order by $orderby ";
    
    $limit = '';    
    if(is_numeric($page) and is_numeric($pagesize)){
        $start = ($page-1) * $pagesize;
        $limit = " LIMIT $start,$pagesize"; // pagination
    }
    $where = '';
    if(!is_null($province_id) and is_numeric($province_id)){
        $where = "where province_id = {$province_id} ";
    }
    # validate fields
    $sql = "select $fields from city $where $orderByStr $limit";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_OBJ);    
    return $records;
}
function getProvinces($data = null){
    global $pdo;
    $sql = "select * from province";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_OBJ);
    return $records;
}


#================  Create Operations  =================
function addCity($data){
    global $pdo;
    if(!isValidCity($data)){
        return false;
    }
    $sql = "INSERT INTO `city` (`province_id`, `name`) VALUES (:province_id, :name);";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':province_id'=>$data['province_id'],':name'=>$data['name']]);
    return $stmt->rowCount();
}
function addProvince($data){
    global $pdo;
    if(!isValidProvince($data)){
        return false;
    }
    $sql = "INSERT INTO `province` (`name`) VALUES (:name);";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':name'=>$data['name']]);
    return $stmt->rowCount();
}


#================  Update Operations  =================
function changeCityName($city_id,$name){
    global $pdo;
    $sql = "update city set name = '$name' where id = $city_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount();
}
function changeProvinceName($province_id,$name){
    global $pdo;
    $sql = "update province set name = '$name' where id = $province_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount();
}

#================  Delete Operations  =================
function deleteCity($city_id){
    global $pdo;
    $sql = "delete from city where id = $city_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount();
}
function deleteProvince($province_id){
    global $pdo;
    $sql = "delete from province where id = $province_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount();
}

#================  Auth Operations  =================
# its our user database 😀
$users = [
    (object)['id'=>1,'name'=>'Loghman','email'=>'loghman@7learn.com','role' => 'admin','allowed_provinces' => []],
    (object)['id'=>2,'name'=>'Sara','email'=>'sara@7learn.com','role' => 'Governor','allowed_provinces' => [7,8,9]],
    (object)['id'=>3,'name'=>'Ali','email'=>'ali@7learn.com','role' => 'mayor','allowed_provinces' => [3]],
    (object)['id'=>4,'name'=>'Hassan','email'=>'hassan@7learn.com','role' => 'president','allowed_provinces' => []]
];
function getUserById($id){
    global $users;
    foreach ($users as $user) 
        if($user->id == $id)
            return $user;
    return null;
}
function getUserByEmail($email){
    global $users;
    foreach ($users as $user) 
        if(strtolower($user->email) == strtolower($email))
            return $user;
    return null;
}

function createApiToken($user){
    $payload = ['user_id' => $user->id];
    return JWT::encode($payload, JWT_KEY, JWT_ALG);
}

function isValidToken($jwt_token){
    try{
        $payload = JWT::decode($jwt_token,JWT_KEY,array(JWT_ALG));
        $user = getUserById($payload->user_id);
        return $user;
    }catch(Exception $e){
        return false;
    }    
}

function hasAccessToProvince($user,$province_id){
    return (in_array($user->role,['admin','president']) or 
            in_array($province_id,$user->allowed_provinces));
}


/** 
 * Get header Authorization
 * */
function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}
/**
* get access token from header
* */
function getBearerToken() {
$headers = getAuthorizationHeader();
// HEADER: Get the access token from the header
if (!empty($headers)) {
    if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
}
return null;
}

// Function Tests
// $data = addCity(['province_id' => 23,'name' => "Loghman Shahr"]);
// $data = addProvince(['name' => "7Learn"]);
// $data = getCities(['province_id' => 23]);
// $data = deleteProvince(34);
// $data = changeProvinceName(34,"سون لرن");
// $data = getProvinces();
// $data = deleteCity(443);
// $data = changeCityName(445,"لقمان شهر");
// $data = getCities(['province_id' => 1]);
// $data = json_encode($data);
// echo "<pre>";
// print_r($data);
// echo "<pre>";
