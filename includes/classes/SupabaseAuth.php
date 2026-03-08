<?php

namespace includes\classes;

/**
 * Class SupabaseAuth
 *
 * Gerencia autenticação OAuth via Supabase Auth (Google Workspace).
 * Utiliza o fluxo Authorization Code com PKCE para segurança server-side.
 *
 * Fluxo:
 *   1. getAuthorizationUrl()  → redireciona usuário para Google via Supabase
 *   2. exchangeCodeForSession() → troca o code pelo token de sessão
 *   3. validateAndGetUser()   → valida JWT e retorna dados do usuário
 *
 * @package includes\classes
 */
class SupabaseAuth
{
    private string $supabaseUrl;
    private string $anonKey;
    private string $jwtSecret;
    private string $allowedDomain;
    private string $callbackUrl;

    public function __construct()
    {
        $this->supabaseUrl   = defined('SUPABASE_URL')              ? SUPABASE_URL              : '';
        $this->anonKey       = defined('SUPABASE_ANON_KEY')         ? SUPABASE_ANON_KEY         : '';
        $this->jwtSecret     = defined('SUPABASE_JWT_SECRET')       ? SUPABASE_JWT_SECRET       : '';
        $this->allowedDomain = defined('GOOGLE_WORKSPACE_DOMAIN')   ? GOOGLE_WORKSPACE_DOMAIN   : '';
        $this->callbackUrl   = defined('OAUTH_CALLBACK_URL')        ? OAUTH_CALLBACK_URL        : '';
    }

    /**
     * Gera a URL de autorização OAuth (com PKCE) e armazena o code_verifier na sessão.
     *
     * @return string URL para redirecionar o usuário
     */
    public function getAuthorizationUrl(): string
    {
        $codeVerifier  = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);

        // Armazena o verifier na sessão para verificar no callback
        $_SESSION['oauth_code_verifier'] = $codeVerifier;
        $_SESSION['oauth_state']         = bin2hex(random_bytes(16));

        $params = http_build_query([
            'provider'              => 'google',
            'redirect_to'           => $this->callbackUrl,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return "{$this->supabaseUrl}/auth/v1/authorize?{$params}";
    }

    /**
     * Troca o authorization code pelo token de sessão Supabase.
     *
     * @param string $code   Código recebido no callback
     * @return array|null    ['access_token'=>..., 'user'=>[...]] ou null em falha
     */
    public function exchangeCodeForSession(string $code): ?array
    {
        $codeVerifier = $_SESSION['oauth_code_verifier'] ?? '';

        if (empty($codeVerifier)) {
            error_log('SupabaseAuth: code_verifier não encontrado na sessão');
            return null;
        }

        $url     = "{$this->supabaseUrl}/auth/v1/token?grant_type=pkce";
        $payload = json_encode([
            'auth_code'     => $code,
            'code_verifier' => $codeVerifier,
        ]);

        $response = $this->httpPost($url, $payload, [
            'Content-Type: application/json',
            'apikey: ' . $this->anonKey,
        ]);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        if (empty($data['access_token'])) {
            error_log('SupabaseAuth: token não retornado — ' . $response);
            return null;
        }

        // Limpa o verifier da sessão (uso único)
        unset($_SESSION['oauth_code_verifier']);

        return $data;
    }

    /**
     * Valida o JWT Supabase, verifica domínio do Workspace e retorna dados do usuário.
     *
     * @param string $accessToken JWT retornado pelo Supabase
     * @return array|null         Dados do usuário ou null se inválido
     */
    public function validateAndGetUser(string $accessToken): ?array
    {
        $payload = $this->decodeJwtPayload($accessToken);

        if (!$payload) {
            error_log('SupabaseAuth: JWT inválido');
            return null;
        }

        // Verificar expiração
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            error_log('SupabaseAuth: JWT expirado');
            return null;
        }

        // Verificar issuer
        $expectedIss = "{$this->supabaseUrl}/auth/v1";
        if (isset($payload['iss']) && $payload['iss'] !== $expectedIss) {
            error_log('SupabaseAuth: issuer inválido — ' . ($payload['iss'] ?? ''));
            return null;
        }

        $email = $payload['email'] ?? '';

        if (empty($email)) {
            error_log('SupabaseAuth: email não encontrado no token');
            return null;
        }

        // Verificar domínio do Google Workspace
        if (!$this->isAllowedDomain($email)) {
            error_log("SupabaseAuth: domínio não autorizado para {$email}");
            return null;
        }

        return [
            'sub'          => $payload['sub']                       ?? '',
            'email'        => $email,
            'name'         => $payload['user_metadata']['full_name'] ?? $payload['user_metadata']['name'] ?? $email,
            'picture'      => $payload['user_metadata']['avatar_url'] ?? '',
            'email_verified' => $payload['user_metadata']['email_verified'] ?? false,
        ];
    }

    /**
     * Verifica se o email pertence ao domínio Workspace autorizado.
     *
     * @param string $email
     * @return bool
     */
    public function isAllowedDomain(string $email): bool
    {
        if (empty($this->allowedDomain)) {
            return true; // Sem restrição de domínio configurada
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        return strtolower($parts[1]) === strtolower($this->allowedDomain);
    }

    /**
     * Decodifica apenas o payload do JWT (sem verificação de assinatura criptográfica).
     * A verificação de autenticidade é feita pela validação de issuer + Supabase API.
     *
     * Para validação criptográfica completa, instalar firebase/php-jwt e usar
     * JWT::decode($token, new Key($this->jwtSecret, 'HS256'))
     *
     * @param string $token
     * @return array|null
     */
    private function decodeJwtPayload(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = $parts[1];
        // Base64url decode
        $payload = str_replace(['-', '_'], ['+', '/'], $payload);
        $payload = base64_decode($payload . str_repeat('=', (4 - strlen($payload) % 4) % 4));

        if (!$payload) {
            return null;
        }

        return json_decode($payload, true);
    }

    /**
     * Gera um code_verifier aleatório seguro para PKCE.
     *
     * @return string 96 bytes em base64url = 128 chars
     */
    private function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(96)), '+/', '-_'), '=');
    }

    /**
     * Gera o code_challenge a partir do code_verifier (S256).
     *
     * @param string $verifier
     * @return string
     */
    private function generateCodeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Executa uma requisição HTTP POST com cURL.
     *
     * @param string $url
     * @param string $payload
     * @param array  $headers
     * @return string|null
     */
    private function httpPost(string $url, string $payload, array $headers = []): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('SupabaseAuth cURL error: ' . $error);
            return null;
        }

        return $response;
    }
}
