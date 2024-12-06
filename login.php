<?php
session_start(); // Iniciar la sesión

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parcialfinal";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}

// Función para generar un token CSRF
function generarToken() {
  return bin2hex(random_bytes(32));
}

// Función para validar y filtrar caracteres no permitidos
function validar_caracteres($input) {
  // Definir los caracteres no permitidos (por ejemplo, < > ' " ; etc.)
  $caracteres_no_permitidos = '/[<>\"\'\;\%\!\#\$\&\=\?\¿\]\[]/'; // Expresión regular para caracteres no permitidos
  if (preg_match($caracteres_no_permitidos, $input)) {
      return false; // Si se encuentran caracteres no permitidos, devolver falso
  }
  return true; // Si no hay caracteres no permitidos, devolver verdadero
}

// Verificar si el usuario está bloqueado por intentos fallidos
if (isset($_SESSION['bloqueo_tiempo']) && time() < $_SESSION['bloqueo_tiempo']) {
  $tiempo_restante = $_SESSION['bloqueo_tiempo'] - time();
  $min_restantes = floor($tiempo_restante / 60);
  $seg_restantes = $tiempo_restante % 60;
  
  die("Has alcanzado el límite de intentos. Intenta nuevamente en $min_restantes minutos y $seg_restantes segundos.");
}

// Si es un método GET, generamos el token y lo almacenamos en la sesión
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $_SESSION['token'] = generarToken();
}

// Si es un método POST, verificamos el token CSRF
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Verificar si el token CSRF enviado es válido
  if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
    die("Error: Token CSRF no válido");
  }

  // Obtener y sanitizar los datos de entrada
  $email = $_POST['email'];
  $pass = $_POST['password'];

  // Verificar si los caracteres son válidos
  if (!validar_caracteres($email)) {
    die("Error: El correo electrónico contiene caracteres no permitidos.");
  }

  if (!validar_caracteres($pass)) {
    die("Error: La contraseña contiene caracteres no permitidos.");
  }

  // Sanitizar los datos antes de la consulta
  $email = $conn->real_escape_string($email);
  $pass = $conn->real_escape_string($pass);

  // Verificar si el usuario existe
  $sql = "SELECT * FROM users WHERE email='$email'";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Verificar la contraseña
    if (password_verify($pass, $row['password'])) {
      echo "Inicio de sesión exitoso";
      // Crear la sesión
      $_SESSION['id'] = $row['id'];
      $_SESSION['intentos'] = 0; // Reiniciar intentos fallidos

      // Reiniciar el token CSRF
      $_SESSION['token'] = generarToken();
      // Actualizar la columna de intentos fallidos en la base de datos
      $sql_update = "UPDATE users SET intentos_fallidos = 0 WHERE email='$email'";
      $conn->query($sql_update);

    } else {
      echo "Contraseña incorrecta";

      // Registrar intento fallido
      $intentos_fallidos = $row['intentos_fallidos'] + 1;

      // Si los intentos fallidos alcanzan 3, bloquear al usuario por 5 minutos
      if ($intentos_fallidos >= 3) {
        $_SESSION['bloqueo_tiempo'] = time() + (5 * 60); // Bloquear por 5 minutos
        die("Has alcanzado el límite de intentos. Intenta nuevamente en 5 minutos.");
      }

      // Actualizar la cantidad de intentos fallidos en la base de datos
      $sql_update = "UPDATE users SET intentos_fallidos = $intentos_fallidos WHERE email='$email'";
      $conn->query($sql_update);
    }
  } else {
    echo "El usuario no existe";

    $intentos_fallidos = 1;

    // Actualizar la columna de intentos fallidos en la base de datos
    $sql_update = "INSERT INTO users (email, password, intentos_fallidos) VALUES ('$email', '$pass', $intentos_fallidos)";
    $conn->query($sql_update);
  }
}
?>



<head>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      padding: 50px;
      text-align: center;
    }
    h1 {
      color: #333;
    }
    form {
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      display: inline-block;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    input[type="submit"] {
      background-color: #4CAF50;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    input[type="submit"]:hover {
      background-color: #45a049;
    }
    a {
      display: flex;
      margin-top: 20px;
      align-items: center;
      justify-content: center;
      color: #007BFF;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>


<!-- Formulario de inicio de sesión -->
<form method="POST" action="login.php">
  <h1 class="nada">Iniciar sesión</h1>
  <input type="text" name="email" placeholder="E-Mail" required><br>
  <input type="password" name="password" placeholder="Contraseña" required><br>
  <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>"><br>
  <input type="submit" value="Iniciar sesión"><br>
  <a href='register.php'>Registrarse</a>
</form>

