<?php
if (!function_exists('rank_secret_key')) {
    function rank_secret_key(): string
    {
        if (isset($_ENV['API_SECRET_KEY_mok']) && $_ENV['API_SECRET_KEY_mok'] !== '') {
            return $_ENV['API_SECRET_KEY_mok'];
        }
        $env = getenv('API_SECRET_KEY_mok');
        return $env !== false ? $env : '';
    }
}

if (!function_exists('rank_b64url_encode')) {
    function rank_b64url_encode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}

if (!function_exists('make_identity_ticket')) {
    /**
     * @param string $uid    mok_user.id
     * @param int    $ttlSec 有效期（秒），默认 2 小时，与登录态一致
     * @return string 空字符串表示密钥缺失，调用方应视为“未签发票据”
     */
    function make_identity_ticket(string $uid, int $ttlSec = 7200): string
    {
        $key = rank_secret_key();
        if ($key === '' || $uid === '') {
            return '';
        }
        $payload = json_encode(
            ['uid' => $uid, 'exp' => time() + $ttlSec],
            JSON_UNESCAPED_UNICODE
        );
        $p = rank_b64url_encode($payload);
        $sig = rank_b64url_encode(hash_hmac('sha256', $p, $key, true));
        return $p . '.' . $sig;
    }
}
