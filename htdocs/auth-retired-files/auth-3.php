function auth_check_credentials(string $username, string $password): bool
{
    $username = trim($username);
    $password = (string)$password;

    // Support both old (plaintext username) and new (hashed username) setups
    if (defined('HC_LOGIN_USERNAME_HASH') && defined('HC_LOGIN_PASSWORD_HASH')) {
        $uh = (string)HC_LOGIN_USERNAME_HASH;
        $ph = (string)HC_LOGIN_PASSWORD_HASH;

        if ($uh === '' || $ph === '') return false;

        $ok_user = password_verify($username, $uh);
        $ok_pass = password_verify($password, $ph);

        return $ok_user && $ok_pass;
    }

    // Legacy fallback (if you still use plaintext username)
    if (defined('HC_LOGIN_USERNAME') && defined('HC_LOGIN_PASSWORD_HASH')) {
        $u = (string)HC_LOGIN_USERNAME;
        $h = (string)HC_LOGIN_PASSWORD_HASH;

        if ($u === '' || $h === '') return false;

        return hash_equals($u, $username) && password_verify($password, $h);
    }

    return false;
}
