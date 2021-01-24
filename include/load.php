<?php
foreach (file("../.env") as $item) {
    $config = explode("=", $item);
    $name = trim($config[0]);
    $value = trim($config[1]);
    define($name, $value);
}
try { // Database connection PDO
    $conn = new PDO(DB_CONNECTION . ':host=' . DB_HOST . ';dbname=' . DB_DATABASE, DB_USERNAME, DB_PASSWORD);
    $conn->exec('set names utf8mb4');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    print 'Error!: ' . $e->getMessage() . '<br/>';
    die();
}
spl_autoload_register(static function ($ClassName) use ($conn) {
    $path = '..' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $ClassName . '.php';
    require $path;
    new $ClassName($conn);
});
