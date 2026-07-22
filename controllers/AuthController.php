<?php
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../helpers/Session.php';
require_once '../helpers/Token.php';

class AuthController {
    /**
     * Validación reCAPTCHA en el login.
     * DESACTIVADA: las llaves reCAPTCHA están registradas por dominio en
     * Google; si la llave no corresponde a este dominio, el login rechaza
     * SIEMPRE con "Credenciales incorrectas" aunque la contraseña sea
     * correcta. Para reactivarla: registra el dominio en
     * https://www.google.com/recaptcha/admin, coloca la clave de sitio en
     * views/login.php, la clave secreta en verifyRecaptcha() y cambia
     * esta constante a true.
     */
    const RECAPTCHA_ENABLED = false;

    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function login($email, $password, $recaptcha_token) {
        // 1️⃣ **Validar el reCAPTCHA (solo si está habilitado)**
        if (self::RECAPTCHA_ENABLED && !$this->verifyRecaptcha($recaptcha_token)) {
            return false;
        }

        // 2️⃣ **Verificar si el usuario existe y la contraseña es correcta**
        $this->user->email = $email;
        if ($this->user->authenticate($password)) {
            // Iniciar sesión
            Session::set('user_id', $this->user->id);
            Session::set('username', $this->user->username);
            return true;
        }
        return false;
    }

    public function logout() {
        Session::destroy();
    }
    
    
    public function register($username, $email, $password) {
    // 1) Verificar si ya existe un usuario con ese email
    $query = "SELECT COUNT(*) FROM users WHERE email = :email";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->fetchColumn() > 0) {
        // Si ya existe un usuario con ese email, retornar false
        return false;
    }

    // 2) Cifrar la contraseña
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // 3) Insertar el nuevo usuario en la base de datos
    $queryInsert = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
    $stmtInsert = $this->db->prepare($queryInsert);
    $stmtInsert->bindParam(':username', $username);
    $stmtInsert->bindParam(':email', $email);
    $stmtInsert->bindParam(':password', $hashedPassword);

    // 4) Ejecutar el INSERT
    return $stmtInsert->execute();
}


    // ✅ **Función para validar reCAPTCHA v3**
    // ✅ **Función para validar reCAPTCHA v3 con cURL**
private function verifyRecaptcha($recaptcha_token) {
    $secretKey = '6Ldij9AqAAAAABC78mrr9EmqASve-sCD33JTx7pA';  // 🔹 Usa tu clave secreta real
    $url = 'https://www.google.com/recaptcha/api/siteverify';

    $data = [
        'secret'   => $secretKey,
        'response' => $recaptcha_token
    ];

    // Inicializar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Ejecutar y cerrar
    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('cURL error: ' . curl_error($ch));
        curl_close($ch);
        return false; // Si hay error de conexión, retornar false
    }

    curl_close($ch);

    $response = json_decode($result, true);

    // Retornar éxito (puedes añadir validación de score si usas v3)
    return isset($response['success']) && $response['success'] === true;
}

}
?>
