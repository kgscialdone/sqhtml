<?php
  // SQHTML v0.1.0 by Katrina Scialdone
  // https://sqhtml.swordglowsblue.com

  // Load database connection settings from environment variables
  [$host,$dbnm,$user,$pass] = array_map('getenv', ['SQHTML_HOST', 'SQHTML_DBNM', 'SQHTML_USER', 'SQHTML_PASS']);

  // Connect to the database
  try { $db = new PDO("mysql:host=$host;dbname=$dbnm;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); }
  catch(\PDOException $e) { throw new \PDOException($e->getMessage(), (int)$e->getCode()); }
  function runQuery($db, string $query, ...$params) { ($stmt = $db->prepare($query))->execute($params); return $stmt; }

  // Set up session variables and select the page
  runQuery($db, 'SET @_PATH = ?; SET @_POST = ?; SET @_GET = ?;', strtok($_SERVER['REQUEST_URI'], '?'), json_encode($_POST), json_encode($_GET));
  if(!($page = runQuery($db, 'SELECT * FROM sqh_pages WHERE path = @_PATH')->fetch())) {
    $page = runQuery($db, 'SELECT * FROM sqh_pages WHERE path = "/404"')->fetch() ?: ['content'=>'Page not found','meta_type'=>'text/plain'];
    http_response_code(404); }

  // Replace includes and embedded SQL blocks until we run out of things to replace
  do {} while($page['content'] != ($page['content'] = preg_replace_callback_array([
    // Basic includes       - [[name]] replaces itself with the matching include row's content column
    '/\\[\\[(.*?)\\]\\]/'   => function($key) use ($db) { return runQuery($db, 'SELECT content FROM sqh_includes WHERE name = ?', $key[1])->fetchColumn(); },
    // Silent SQL blocks    - {{!query}} executes the contained query and disappears
    '/\\{\\{!(.*?)\\}\\}/s' => function($qry) use ($db) { runQuery($db, $qry[1]); return ''; },
    // Normal SQL blocks    - {{query}} executes the contained query and replaces itself with the contents of the first result column
    '/\\{\\{(.*?)\\}\\}/s'  => function($qry) use ($db) { return implode("\n", runQuery($db, $qry[1])->fetchAll(PDO::FETCH_COLUMN, 0)); },
  ], $page['content'])));

  // Dump the resulting contents into the response
  header("Content-Type: $page[meta_type]");
  echo $page['content'];
?>

