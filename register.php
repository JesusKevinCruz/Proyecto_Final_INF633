<?php
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

// Función para validar y filtrar caracteres no permitidos
function validar_caracteres($input) {
  // Definir los caracteres no permitidos (por ejemplo, < > ' " ; etc.)
  $caracteres_no_permitidos = '/[<>\"\'\;\%\!\#\$\&\=\?\¿\[\]\{\}\^]/';
  if (preg_match($caracteres_no_permitidos, $input)) {
      return false; // Si se encuentran caracteres no permitidos, devolver falso
  }
  return true; // Si no hay caracteres no permitidos, devolver verdadero
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Obtener el email y la contraseña del formulario
  $email = $_POST['email'];
  $pass = $_POST['password'];

  // Validar el correo electrónico
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "El correo electrónico no es válido.";
  } else {
    // Validar que el correo no contenga caracteres no permitidos
    if (!validar_caracteres($email)) {
      echo "El correo electrónico contiene caracteres no permitidos.";
    } else {
      // Validar que la contraseña no contenga caracteres no permitidos
      if (!validar_caracteres($pass)) {
        echo "La contraseña contiene caracteres no permitidos.";
      } else {
        // Escapar los valores para evitar inyección SQL
        $email = $conn->real_escape_string($email);
        $pass = $conn->real_escape_string($pass);

        // Hashear la contraseña antes de almacenarla
        $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

        // Insertar el usuario en la base de datos
        $sql = "INSERT INTO users (email, password) VALUES('$email', '$hashed_password')";

        if ($conn->query($sql) === TRUE) {
          echo "Registro exitoso.";
        } else {
          echo "Error: " . $conn->error;
        }
      }
    }
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

<!-- Formulario de registro -->
<form method="POST" action="register.php">
  <h1>Registrarse</h1>
  <input type="text" name="email" placeholder="E-Mail" required><br>
  <input type="password" name="password" placeholder="Contraseña" required><br>
  <input type="submit" value="Registrarse"><br>
  <a href='login.php'>Iniciar Sesión</a>
</form>
