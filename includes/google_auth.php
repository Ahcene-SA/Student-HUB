<?php
/**
 * Handler du callback OAuth Google.
 *
 * Le redirect_uri configuré côté Google pointe sur /profile/profile.php,
 * qui appelle handle_google_callback() quand un ?code=... est présent.
 *
 * Variables d'environnement attendues (à définir dans Dokploy) :
 *   GOOGLE_CLIENT_ID     (a un fallback ci-dessous)
 *   GOOGLE_CLIENT_SECRET (OBLIGATOIRE — pas de fallback)
 *   GOOGLE_REDIRECT_URI  (doit correspondre EXACTEMENT à l'URI envoyée à Google)
 */

function get_google_config() {
    return array(
        'client_id'     => getenv('GOOGLE_CLIENT_ID') ?: '669251997046-vbvcsr40nqgc56hsbvorun6honh13smd.apps.googleusercontent.com',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri'  => getenv('GOOGLE_REDIRECT_URI') ?: 'https://studenthub.cloud/profile/profile.php',
    );
}

/**
 * Construit l'URL d'autorisation Google (étape d'INITIATION).
 * Le bouton « Continuer avec Google » pointe sur cette URL ; Google
 * renvoie ensuite l'utilisateur sur le redirect_uri avec un ?code=...
 */
function get_google_auth_url() {
    $cfg = get_google_config();
    $params = array(
        'client_id'     => $cfg['client_id'],
        'redirect_uri'  => $cfg['redirect_uri'],
        'response_type' => 'code',
        'scope'         => 'email profile',
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    );
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/** Petit helper HTTP POST (form-urlencoded) renvoyant le corps brut. */
function google_http_post($url, array $fields) {
    $body = http_build_query($fields);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $resp = curl_exec($ch);
        if ($resp === false) {
            error_log('Google OAuth cURL POST error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $resp;
    }

    $ctx = stream_context_create(array('http' => array(
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $body,
        'timeout' => 15,
    )));
    return @file_get_contents($url, false, $ctx);
}

/** Petit helper HTTP GET avec Bearer token. */
function google_http_get($url, $accessToken) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $resp = curl_exec($ch);
        if ($resp === false) {
            error_log('Google OAuth cURL GET error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $resp;
    }

    $ctx = stream_context_create(array('http' => array(
        'method'  => 'GET',
        'header'  => "Authorization: Bearer " . $accessToken . "\r\n",
        'timeout' => 15,
    )));
    return @file_get_contents($url, false, $ctx);
}

/** Redirige vers la page de connexion avec un message d'erreur. */
function google_fail($message) {
    $_SESSION['signin_error'] = $message;
    header('Location: ../persoinfo/signin.php#form-signin');
    exit();
}

/**
 * Termine le flux OAuth : échange le code, récupère le profil,
 * trouve/crée l'utilisateur, ouvre la session, puis redirige.
 */
function handle_google_callback(PDO $pdo) {
    $cfg = get_google_config();

    if ($cfg['client_secret'] === '') {
        error_log('Google OAuth: GOOGLE_CLIENT_SECRET non défini.');
        google_fail("Connexion Google indisponible (configuration serveur manquante).");
    }

    $code = isset($_GET['code']) ? $_GET['code'] : '';
    if ($code === '') {
        google_fail("Code d'autorisation Google manquant.");
    }

    // 1) Échange du code contre un access_token
    $tokenRaw = google_http_post('https://oauth2.googleapis.com/token', array(
        'code'          => $code,
        'client_id'     => $cfg['client_id'],
        'client_secret' => $cfg['client_secret'],
        'redirect_uri'  => $cfg['redirect_uri'],
        'grant_type'    => 'authorization_code',
    ));

    $token = json_decode((string) $tokenRaw, true);
    if (empty($token['access_token'])) {
        error_log('Google OAuth: échec de l\'échange de token: ' . $tokenRaw);
        google_fail("Échec de la connexion Google. Réessayez.");
    }

    // 2) Récupération des infos du profil
    $userRaw = google_http_get('https://www.googleapis.com/oauth2/v3/userinfo', $token['access_token']);
    $info = json_decode((string) $userRaw, true);

    if (empty($info['email'])) {
        error_log('Google OAuth: userinfo sans email: ' . $userRaw);
        google_fail("Impossible de récupérer votre adresse e-mail Google.");
    }

    // Optionnel : n'accepter que les e-mails vérifiés
    if (isset($info['email_verified']) && $info['email_verified'] === false) {
        google_fail("Votre adresse Google n'est pas vérifiée.");
    }

    $email  = $info['email'];
    $prenom = !empty($info['given_name'])  ? $info['given_name']  : (explode('@', $email)[0]);
    $nom    = !empty($info['family_name']) ? $info['family_name'] : '';

    // 3) Utilisateur existant ?
    $stmt = $pdo->prepare("SELECT id_user FROM user WHERE email = ? LIMIT 1");
    $stmt->execute(array($email));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $idUser = (int) $row['id_user'];
    } else {
        $idUser = google_create_user($pdo, $prenom, $nom, $email);
    }

    // 4) Session + redirection propre (retire le ?code de l'URL)
    session_regenerate_id(true);
    $_SESSION['id_user'] = $idUser;
    header('Location: profile.php');
    exit();
}

/**
 * Crée un compte minimal pour un nouvel utilisateur Google.
 * Gère l'unicité de `username` (contrainte UNIQUE) et le mot de passe
 * aléatoire (la colonne `mdp` est UNIQUE NOT NULL).
 */
function google_create_user(PDO $pdo, $prenom, $nom, $email) {
    $base = preg_replace('/[^a-zA-Z0-9._-]/', '', explode('@', $email)[0]);
    if ($base === '') {
        $base = 'user';
    }

    $ins = $pdo->prepare(
        "INSERT INTO user (prenom, nom, username, email, mdp, filliere, school)
         VALUES (?, ?, ?, ?, ?, '', '')"
    );

    // Quelques tentatives en cas de collision de username
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $username   = $attempt === 0 ? $base : $base . bin2hex(random_bytes(2));
        $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        try {
            $ins->execute(array($prenom, $nom, $username, $email, $randomPass));
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            // 23000 = violation de contrainte d'unicité
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            // Si c'est l'email qui est en double (course entre 2 requêtes), on récupère l'id existant
            $check = $pdo->prepare("SELECT id_user FROM user WHERE email = ? LIMIT 1");
            $check->execute(array($email));
            $found = $check->fetch(PDO::FETCH_ASSOC);
            if ($found) {
                return (int) $found['id_user'];
            }
            // sinon c'est sûrement le username -> on retente avec un suffixe
        }
    }

    error_log('Google OAuth: impossible de créer un username unique pour ' . $email);
    google_fail("Impossible de créer votre compte. Réessayez.");
}
