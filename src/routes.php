<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

#### GET ####

/* Obtener todas las reservas */

$app->get('/reservas', function (Request $request, Response $response, array $args) {
    $sql = "select re.id, pi.nombre_pista, pi.thumbnail, re.hora_pista, re.dia_pista from reservas re, pistas pi, usuarios us where re.id_pista = pi.id and re.id_usuario = us.id and re.dia_pista >= CURRENT_DATE() ORDER BY re.dia_pista, re.hora_pista";
    $sth = $this->db->prepare($sql);
    $sth->execute();
    $reservas = $sth->fetchAll();
    return $this->response->withJson(["reservas"=>$reservas], 200, JSON_PRETTY_PRINT);
});

/* Obtener todas las pistas con sus hora */

$app->get('/pistas', function (Request $request, Response $response, array $args) {
    $sql = 'select pi.nombre_pista, GROUP_CONCAT(hp.hora) as horas, pi.thumbnail from pistas pi, horas_pista hp where pi.tipo_horas = hp.tipo GROUP by pi.nombre_pista';
    $sth = $this->db->prepare($sql);
    $sth->execute();
    $pistas = $sth->fetchAll();
    return $this->response->withJson(["pistas"=>$pistas], 200, JSON_PRETTY_PRINT);
});

/* Obtener todos los usuarios */

$app->get('/usuarios', function (Request $request, Response $response, array $args) {
    $sql = "SELECT * FROM usuarios;";
    $sth = $this->db->prepare($sql);
    $sth->execute();
    $usuarios = $sth->fetchAll();
    return $this->response->withJson(["usuarios"=>$usuarios], 200, JSON_PRETTY_PRINT);
});

/* Obtener todos los jugadores */

$app->get('/jugadores', function (Request $request, Response $response, array $args) {
    $sql = "SELECT * FROM jugadores;";
    $sth = $this->db->prepare($sql);
    $sth->execute();
    $jugadores = $sth->fetchAll();
    return $this->response->withJson(["jugadores"=>$jugadores], 200, JSON_PRETTY_PRINT);
});

/* Obtener todas las reservas con todos los datos de los jugadores */

$app->get('/historial', function (Request $request, Response $response, array $args) {
    $sql = "SELECT reservas.id as id, nombre, email, telefono, nombre_pista, hora_pista, DATE_FORMAT(dia_pista,'%d/%m/%Y') AS 'dia_pista' FROM reservas INNER JOIN pistas ON reservas.id_pista = pistas.id INNER JOIN usuarios ON reservas.id_usuario = usuarios.id ORDER BY dia_pista";
    $sth = $this->db->prepare($sql);
    $sth->execute();
    $historial = $sth->fetchAll();
    return $this->response->withJson(["historial"=>$historial], 200, JSON_PRETTY_PRINT);
});

/* Obtener todos los jugadores de una reserva */

$app->get('/jugadores/[{id_reserva}]', function (Request $request, Response $response, array $args) {
    $sql = "SELECT * FROM jugadores WHERE id_reserva = :id_reserva;";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("id_reserva", $args['id_reserva']);
    $sth->execute();
    $jugadores = $sth->fetchAll();
    if($jugadores) return $this->response->withJson(["jugadores"=>$jugadores], 200, JSON_PRETTY_PRINT);
    else return $this->response->withJson(["error"=>"Jugadores no encontrados."], 404, JSON_PRETTY_PRINT);
});

/* Obtener todos los jugadores de una reserva con sus datos */

$app->get('/nombre/[{id_reserva}]', function (Request $request, Response $response, array $args) {
    $sql = "SELECT jug.id, usu.nombre, usu.apellidos, usu.nivel, usu.posicion FROM jugadores jug, usuarios usu, reservas res WHERE jug.id_usuario = usu.id AND res.id = jug.id_reserva AND id_reserva = :id_reserva ORDER BY jug.id;";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("id_reserva", $args['id_reserva']);
    $sth->execute();
    $jugadores = $sth->fetchAll();
    if($jugadores) return $this->response->withJson(["jugadores"=>$jugadores], 200, JSON_PRETTY_PRINT);
    else return $this->response->withJson(["error"=>"Jugadores no encontrados."], 404, JSON_PRETTY_PRINT);
});

/* Obtener una reserva por su id */

$app->get('/reserva/[{id}]', function (Request $request, Response $response, array $args) {
    $sql = "SELECT re.id_usuario, re.hora_pista, re.dia_pista, re.comentario, pi.nombre_pista, pi.thumbnail FROM reservas re, pistas pi WHERE pi.id = re.id_pista and re.id = :id and re.dia_pista >= CURRENT_DATE() ORDER BY re.dia_pista, re.hora_pista;";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("id", $args['id']);
    $sth->execute();
    $reserva = $sth->fetchObject();
    if($reserva) return $this->response->withJson(["reserva"=>$reserva], 200, JSON_PRETTY_PRINT);
    else return $this->response->withJson(["error"=>"Reserva no encontrada."], 404, JSON_PRETTY_PRINT);
});

/* Obtener una pista por su id */

$app->get('/pista/[{id}]', function (Request $request, Response $response, array $args) {
    $sql = "SELECT * FROM pistas WHERE id = :id;";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("id", $args['id']);
    $sth->execute();
    $pista = $sth->fetchObject();
    if($pista) return $this->response->withJson(["pista"=>$pista], 200, JSON_PRETTY_PRINT);
    else return $this->response->withJson(["error"=>"Reserva no encontrada."], 404, JSON_PRETTY_PRINT);
});

/* Obtener el jugador de una reserva */

$app->get('/jugadores/{id_usuario}/{id_reserva}', function (Request $request, Response $response, array $args) {
    $sql = "SELECT * FROM jugadores WHERE id_usuario = :id_usuario AND id_reserva = :id_reserva;";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("id_usuario", $args['id_usuario']);
    $sth->bindParam("id_reserva", $args['id_reserva']);
    $sth->execute();
    $jugadores = $sth->fetchObject();
    if($jugadores) return $this->response->withJson(["jugadores"=>$jugadores], 200, JSON_PRETTY_PRINT);
    else return $this->response->withJson(["error"=>"Jugador no encontrado en esa reserva."], 404, JSON_PRETTY_PRINT);
});

#### PUT ####

/* Actualizar una reserva por id */

$app->put('/reserva/[{id}]', function ($request, $response, $args) {
    $input = $request->getParsedBody();
    $sql = "UPDATE reservas SET nombre=:nombre, descripcion=:descripcion WHERE id=:id";
     $sth = $this->db->prepare($sql);
    $sth->bindParam("id", $args['id']);
    $sth->bindParam("nombre", $input['nombre']);
    $sth->execute();
    $input['id'] = $args['id'];
    return $this->response->withJson($input);
});

/* Actualizar un usuario */

$app->put('/usuario/[{id}]', function ($request, $response, $args) {
    $input = $request->getParsedBody();
    $sql = "UPDATE usuarios SET nombre=:nombre, apellidos=:apellidos, email=:email, telefono=:telefono, fecha=:fecha, nivel=:nivel, posicion=:posicion WHERE id=:id";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("id", $args['id']);
    $sth->bindParam("nombre", $input['nombre']);
    $sth->bindParam("apellidos", $input['apellidos']);
    $sth->bindParam("email", $input['email']);
    $sth->bindParam("telefono", $input['telefono']);
    $sth->bindParam("fecha", $input['fecha']);
    $sth->bindParam("nivel", $input['nivel']);
    $sth->bindParam("posicion", $input['posicion']);
    $sth->execute();
    $input['id'] = $args['id'];
    return $this->response->withJson($input);
});


#### POST ####

/* Insertar una nueva pista */

$app->post('/nueva/pista', function ($request, $response) {
    $input = $request->getParsedBody();
    $sql = "INSERT INTO pistas (nombre_pista) VALUES (:nombre_pista)";
     $sth = $this->db->prepare($sql);
    $sth->bindParam("nombre_pista", $input['nombre_pista']);
    $sth->execute();
    $input['id'] = $this->db->lastInsertId();
    return $this->response->withJson($input);
});

/* Insertar una nueva reserva */

$app->post('/nueva/reserva', function ($request, $response) {
    $input = $request->getParsedBody();
    $sql = "INSERT INTO reservas (nombre, descripcion) VALUES (:nombre, :descripcion)";
     $sth = $this->db->prepare($sql);
    $sth->bindParam("nombre", $input['nombre']);
    $sth->bindParam("descripcion", $input['descripcion']);
    $sth->execute();
    $input['id'] = $this->db->lastInsertId();
    return $this->response->withJson($input);
});

/* Insertar un nuevo jugador */

$app->post('/nuevo/jugador', function ($request, $response) {
    $input = $request->getParsedBody();
    $sql = "INSERT INTO jugadores (id_usuario, id_reserva) VALUES (:id_usuario, :id_reserva)";
     $sth = $this->db->prepare($sql);
    $sth->bindParam("id_usuario", $input['id_usuario']);
    $sth->bindParam("id_reserva", $input['id_reserva']);
    $sth->execute();
    $input['id'] = $this->db->lastInsertId();
    return $this->response->withJson($input);
});


##### DELETE #####

/* Borrar una reserva por id */

$app->delete('/borrar/reserva/[{id}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("DELETE FROM reservas WHERE id=:id");
   $sth->bindParam("id", $args['id']);
   $sth->execute();
   return $this->response->withJson(['status' => "OK"], 201);
});

/* Borrar una pista por id */

$app->delete('/borrar/pista/[{id}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("DELETE FROM pistas WHERE id=:id");
   $sth->bindParam("id", $args['id']);
   $sth->execute();
   return $this->response->withJson(['status' => "OK"], 201);
});

/* Borrar un usuario por id */

$app->delete('/borrar/usuario/[{id}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("DELETE FROM usuarios WHERE id=:id");
   $sth->bindParam("id", $args['id']);
   $sth->execute();
   return $this->response->withJson(['status' => "OK"], 201);
});

/* Borrar un jugador por id */

$app->delete('/borrar/jugador/[{id}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("DELETE FROM jugadores WHERE id=:id");
   $sth->bindParam("id", $args['id']);
   $sth->execute();
   return $this->response->withJson(['status' => "OK"], 201);
});